plugins: src/plugins/*/*.php src/framework/vnag_framework.inc.php
	php --define phar.readonly=0 src/build.phps

clean:
	rm -f bin/*.phar
	rm -f bin/*.conf
