#!/bin/bash

DIR=$( dirname "$0" )

setexec () {

	svn propset svn:executable on $*
	svn propdel svn:executable $*
	chmod +x $*

}

setexec "$DIR"/set_chmod.sh
setexec "$DIR"/bin/*.phar
setexec "$DIR"/plugins/ipfm/dygraph/update-dygraph.sh
