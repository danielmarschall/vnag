#!/bin/bash

DIR=$( dirname "$0" )

setexec () {

	svn propdel svn:executable $*
	svn propset svn:executable on $*
	chmod +x $*

}

setexec "$DIR"/set_chmod.sh
setexec "$DIR"/bin/*.phar
setexec "$DIR"/src/plugins/ipfm/dygraph/update-dygraph.sh
