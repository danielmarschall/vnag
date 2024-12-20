
VNag plugin overview
====================

+---------------------------+----------------------------+----------------+-------------------+-----------------------------------------------------------------------+
| 2011 StatMon (deprecated) | 2018 VNag Command          | WebOptimized?  | Generic/Internal? | Notes                                                                 |
+---------------------------+----------------------------+----------------+-------------------+-----------------------------------------------------------------------+
| 4images_version           | vnag_4images_version       | No             | Generic           |                                                                       |
| aptitude_monitor          | (Obsolete)                 | -              | -                 | Functionality part of Icinga's command "apt".                         |
| certificate_monitor       | vnag_x509_expire           | No             | Generic           | See also Icinga's "ssl" command, which checks certs of live websites. |
| hdd_usage (hdd_size)      | (Obsolete)                 | -              | -                 | Functionality part of Icinga's command "disk".                        |
| ipfm_mon                  | vnag_ipfm                  | Yes            | Generic           |                                                                       |
| joomla_version            | vnag_joomla_version        | No             | Generic           |                                                                       |
| lastmon                   | vnag_last                  | No             | Generic           |                                                                       |
| mdstat_test               | vnag_mdstat                | No             | Generic           |                                                                       |
| mediawiki_version         | vnag_mediawiki_version     | No             | Generic           |                                                                       |
| mem                       | vnag_virtual_mem           | No             | Generic           | Checks real+swap memory. Icinga's "swap" command only checks swap.    |
| net2ftp_version           | vnag_net2ftp_version       | No             | Generic           |                                                                       |
| nocc_version              | vnag_nocc_version          | No             | Generic           |                                                                       |
| open_deleted_files        | vnag_open_deleted_files    | No             | Generic           | To see the files which are inaccessible, run "lsof -n | grep deleted" |
| openbugbounty             | vnag_openbugbounty         | No             | Generic           |                                                                       |
| phpbb3_version            | vnag_phpbb_version         | No             | Generic           |                                                                       |
| phpmyadmin_version        | vnag_phpmyadmin_version    | No             | Generic           |                                                                       |
| phppgadmin_version        | vnag_phppgadmin_version    | No             | Generic           |                                                                       |
| ping                      | vnag_ping                  | Yes            | Generic           |                                                                       |
| positive_responder        | (Discontinued)             | -              | -                 | Was only intended as test monitor.                                    |
| roundcube_version         | vnag_roundcube_version     | No             | Generic           |                                                                       |
| smart_test                | vnag_smart                 | No             | Generic           |                                                                       |
| static_ping               | (Obsolete)                 | -              | -                 | Functionality part of Icinga's command "ping-common".                 |
| timestamp                 | vnag_file_timestamp        | No             | Generic           |                                                                       |
| universal_ping            | (Obsolete)                 | -              | -                 | Functionality part of Icinga's command "ping-common".                 |
| verteiler                 | (Obsolete)                 | -              | -                 | Functionality part of Icinga.                                         |
| website_monitor           | (Discontinued)             | -              | -                 |                                                                       |
| wordpress_version         | vnag_wordpress_version     | No             | Generic           |                                                                       |
|                           | vnag_webreader             | No             | Generic           |                                                                       |
|                           | vnag_disk_running          | No             | Generic           | For monitoring disks which do not have SMART functionality.           |
|                           | vnag_pmwiki_version        | No             | Generic           |                                                                       |
|                           | vnag_gitlab_version        | No             | Generic           |                                                                       |
|                           | vnag_nextcloud_version     | No             | Generic           |                                                                       |
|                           | vnag_owncloud_version      | No             | Generic           |                                                                       |
|                           | vnag_hp_smartarray         | No             | Generic           |                                                                       |
|                           | vnag_minecraft_java_version| No             | Generic           |                                                                       |
|                           | vnag_aastra_430_voicemail  | No             | Generic           |                                                                       |
|                           | vnag_websvn_version        | No             | Generic           |                                                                       |
|                           | vnag_viewvc_version        | No             | Generic           |                                                                       |
+---------------------------+----------------------------+----------------+-------------------+-----------------------------------------------------------------------+
|                           | vnag_faindex               | No             | VTS Internal      |                                                                       |
|                           | vnag_fasearch              | No             | VTS Internal      |                                                                       |
|                           | vnag_bulbcam_* (Discont.)  | No             | VTS Internal      |                                                                       |
|                           | vnag_oidinfo_linkcheck     | No             | VTS Internal      |                                                                       |
|                           | vnag_vts_command_listener  | No             | VTS Internal      |                                                                       |
|                           | vnag_vwi_timestamp         | Yes            | VTS Internal      | Available here: https://whois.viathinksoft.de/vnag/                                                                      |
+---------------------------+----------------------------+----------------+-------------------+-----------------------------------------------------------------------+
