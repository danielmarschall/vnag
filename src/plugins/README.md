VNag plugin overview
====================

| VNag Command               | WebOptimized?  | Generic/Internal? | Notes                                                                 |
|----------------------------|----------------|-------------------|-----------------------------------------------------------------------|
| vnag_4images_version       | No             | Generic           |                                                                       |
| vnag_aastra_430_voicemail  | No             | Generic           |                                                                       |
| vnag_bulbcam_*             | No             | VTS Internal      | Discontinued.                                                         |
| vnag_disk_running          | No             | Generic           | For monitoring disks which do not have SMART functionality.           |
| vnag_faindex               | No             | VTS Internal      |                                                                       |
| vnag_fasearch              | No             | VTS Internal      |                                                                       |
| vnag_file_timestamp        | No             | Generic           |                                                                       |
| vnag_gitlab_version        | No             | Generic           |                                                                       |
| vnag_hp_smartarray         | No             | Generic           |                                                                       |
| vnag_ipfm                  | Yes            | Generic           |                                                                       |
| vnag_joomla_version        | No             | Generic           |                                                                       |
| vnag_last                  | No             | Generic           |                                                                       |
| vnag_mdstat                | No             | Generic           |                                                                       |
| vnag_mediawiki_version     | No             | Generic           |                                                                       |
| vnag_minecraft_java_version| No             | Generic           |                                                                       |
| vnag_net2ftp_version       | No             | Generic           |                                                                       |
| vnag_nextcloud_version     | No             | Generic           |                                                                       |
| vnag_nocc_version          | No             | Generic           |                                                                       |
| vnag_oidinfo_linkcheck     | No             | VTS Internal      |                                                                       |
| vnag_openbugbounty         | No             | Generic           |                                                                       |
| vnag_open_deleted_files    | No             | Generic           | To see the files which are inaccessible, run "lsof -n | grep deleted" |
| vnag_owncloud_version      | No             | Generic           |                                                                       |
| vnag_phpbb_version         | No             | Generic           |                                                                       |
| vnag_phpmyadmin_version    | No             | Generic           |                                                                       |
| vnag_phppgadmin_version    | No             | Generic           |                                                                       |
| vnag_ping                  | Yes            | Generic           |                                                                       |
| vnag_pmwiki_version        | No             | Generic           |                                                                       |
| vnag_roundcube_version     | No             | Generic           |                                                                       |
| vnag_smart                 | No             | Generic           |                                                                       |
| vnag_synflood              | No             | Generic           |                                                                       |
| vnag_viewvc_version        | No             | Generic           |                                                                       |
| vnag_virtual_mem           | No             | Generic           | Checks real+swap memory. Icinga's "swap" command only checks swap.    |
| vnag_vts_command_listener  | No             | VTS Internal      |                                                                       |
| vnag_vwi_timestamp         | Yes            | VTS Internal      | Available here: https://whois.viathinksoft.de/vnag/                                                                      |
| vnag_webreader             | No             | Generic           |                                                                       |
| vnag_websvn_version        | No             | Generic           |                                                                       |
| vnag_wordpress_version     | No             | Generic           |                                                                       |
| vnag_x509_expire           | No             | Generic           | See also Icinga's "ssl" command, which checks certs of live websites. |
