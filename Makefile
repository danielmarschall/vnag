plugins: plugins/*/*.php framework/vnag_framework.inc.php
	php --define phar.readonly=0 build.phps

clean:
	rm -f bin/*.phar
	rm -f bin/*.conf
