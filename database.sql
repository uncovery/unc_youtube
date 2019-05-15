CREATE TABLE `youtube_list` (
  `id` varchar(32) NOT NULL,
  `title_raw` varchar(256) NOT NULL,
  `thumb_url` varchar(256) NOT NULL,
  `band` varchar(64) NOT NULL,
  `song` varchar(128) NOT NULL,
  `artist` varchar(32) NOT NULL,
  `date_field` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `youtube_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `band` (`band`),
  ADD KEY `artist` (`artist`),
  ADD KEY `date_field` (`date_field`);
COMMIT;