<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

global $YOUTUBE;

require_once('config.php');

// get the playlist ID
$uploads_id = youtube_channel_uploads_id();

// get the video data from youtube
$data = youtube_query_all($uploads_id);

// write the data to the DB
youtube_write_data($data);

function youtube_write_data($data) {

    $count = count($data);

    echo "Done reading data, adding $count entries to database now!";
    foreach ($data as $video) {
        $id = umc_mysql_real_escape_string($video['id']);
        $title = umc_mysql_real_escape_string($video['title']);
        $thumb = umc_mysql_real_escape_string($video['thumb']);
        $date = umc_mysql_real_escape_string($video['date']);

        $regex = '/(?<band>.*): (?<song>.*) \((?<artist>.*)\)/';
        $matches = false;
        preg_match($regex, $title, $matches);

        $band = '';
        $song = '';
        $artist = '';
        if (isset( $matches['band'])) {
            $band =  umc_mysql_real_escape_string($matches['band']);
            $song = umc_mysql_real_escape_string(str_replace('"', '', $matches['song']));
            $artist = umc_mysql_real_escape_string($matches['artist']);
        }
        $insert_sql = "INSERT INTO `youtube_list`(`id`, `title_raw`, `thumb_url`, `band`, `song`, `artist`, `date_field`) VALUES ($id, $title, $thumb, $band, $song, $artist, $date);";
        umc_mysql_execute_query($insert_sql);
    }
}

/**
 * This gets the playlist ID of all uploads (YouTube creates a playlist for this)
 * instructions here: https://stackoverflow.com/questions/18953499/youtube-api-to-fetch-all-videos-on-a-channel/36387404#36387404
 *
 * @global array $YOUTUBE
 * @return type
 */
function youtube_channel_uploads_id() {
    global $YOUTUBE;
    // get the uploads playlist:
    $get_upload_playlist = "https://www.googleapis.com/youtube/v3/channels?id={$YOUTUBE['channel']}&key={$YOUTUBE['api_key']}&part=contentDetails";


    $playlist_result = unc_serial_curl($get_upload_playlist, 0, 50, $YOUTUBE['curl_cert']);

    $playlist_id_raw = $playlist_result[0]['content'];
    $playlist_id_arr = json_decode($playlist_id_raw, true);

    $uploads_id = $playlist_id_arr['items'][0]['contentDetails']["relatedPlaylists"]["uploads"];

    // UUia7ls4uXZf6NpcUZQisZCg

    return $uploads_id;
}

/**
 * Get the actual URL and it's result, then iterate all successive pages that come out
 * instructions here: https://stackoverflow.com/questions/18953499/youtube-api-to-fetch-all-videos-on-a-channel/36387404#36387404
 *
 * @global array $YOUTUBE
 * @param type $uploads_id
 * @return type
 */
function youtube_query_all($uploads_id) {
    global $YOUTUBE;

    $maxresults = 50;

    $new_data = array();
    $nextpage_token = '';

    // query for the whole list with default fields
    // GET https://www.googleapis.com/youtube/v3/playlistItems?playlistId=$uploads_id&key={$YOUTUBE['api_key']}&part=snippet&maxResults=50
    // with selected fields:
    // GET part=snippet&playlistId=UUia7ls4uXZf6NpcUZQisZCg&fields=items(snippet(publishedAt%2CresourceId%2FvideoId%2Cthumbnails%2Fhigh%2Ctitle))%2CnextPageToken%2CprevPageToken&key={YOUR_API_KEY}

    // youtube API explorer link:
    // https://developers.google.com/apis-explorer/#p/youtube/v3/youtube.playlistItems.list?part=snippet&playlistId=UUia7ls4uXZf6NpcUZQisZCg&fields=items(snippet(publishedAt%252CresourceId%252FvideoId%252Cthumbnails%252Fhigh%252Ctitle))%252CnextPageToken%252CprevPageToken&_h=5&


    // $part = "&part=snippet.publishedAt,snippet.title,id";
    $part = "&part=snippet";
    $fields = "&fields=" . urlencode("items(snippet(publishedAt,resourceId/videoId,thumbnails/high,title)),nextPageToken,pageInfo,prevPageToken");


    $page = true;
    $getpage = '';
    $parsed = 0;
    while ($page) {
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?key={$YOUTUBE['api_key']}&playlistId=$uploads_id$part&order=date&type=video&maxResults=$maxresults$getpage$fields";
        echo "New Page $nextpage_token with <a href=\"$url\">url</a><br>\n";
        $result = unc_serial_curl($url, 0, 50, $YOUTUBE['curl_cert']);
        $data = $result[0]['content'];

        // var_dump($data);

        $data_arr = json_decode($data, true);

        if (!isset($data_arr['items'])) {
            //var_dump($data);
            // return;
        }

        $videos = $data_arr['items'];
        $count = count($videos);
        if ($count == 0) {
            $page = false;
        }

        $parsed = $parsed + $count;

        $total_videos = $data_arr['pageInfo']['totalResults'];
        $this_result = $data_arr['pageInfo']['resultsPerPage'];

        echo "Data array contains $total_videos, this query is supposed to have $this_result, Found $count videos.<br>\n";

        if ($total_videos < 50 && $total_videos != $count) {
            echo "ERROR 1: Found $total_videos, data contains only $count" ;
        } else if ($total_videos > 50 && $count < 50 && $parsed < $total_videos) {
            echo "ERROR 2: Found $total_videos, parsed $parsed only, last array contains only $count";
        }

        foreach ($videos as $video) {
            echo ".";
            $new_data[] = array(
                'id' => $video['snippet']['resourceId']['videoId'],
                'title' => $video['snippet']['title'],
                'thumb' => $video['snippet']['thumbnails']['high']['url'],
                'date' => $video['snippet']['publishedAt'],
            );
        }
        if (!isset($data_arr['nextPageToken']))   {
            $page = false;
        } else {
            $nextpage_token = $data_arr['nextPageToken'];
            $getpage = "&pageToken=$nextpage_token";
        }
        echo "<br>\n";
    }

    return $new_data;
}
