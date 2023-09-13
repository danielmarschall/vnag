#!/bin/bash

DIR=$( dirname "$0" )

setexec () {

	svn propset svn:executable on $1
	svn propdel svn:executable $1
	chmod +x $1

}

setexec "$DIR"/set_chmod.sh
setexec "$DIR"/signtool/sign
setexec "$DIR"/signtool/verify
setexec "$DIR"/sign_all
setexec "$DIR"/plugins/*/check_*
setexec "$DIR"/plugins/ipfm/dygraph/update-dygraph.sh
