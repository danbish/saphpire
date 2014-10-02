#!/bin/bash

# get current working directory.
sPwd=`pwd`

echo $sPwd

# sanity check. CORE | www | html | public
if [[ "$sPwd" != *"libs/PHPCore/cli"* ]] || [[ "$sPwd" != *"www"* ]] || [[ "$sPwd" != *"html"* ]] || [[ "$sPwd" != *"public"* ]]
    then
        echo "Must be run inside CORE."
        exit 0
fi

# get back to vHost root.
cd "${sPwd}"/../../../

# get vhost root
sVHostRoot=`pwd`

# permissions
chmod -f 775 "$sVHostRoot"
chmod -f 775 "$sVHostRoot/classes"
chmod -f 775 "$sVHostRoot/cli"
chmod -fR 750 "$sVHostRoot/configs"
chmod -f 775 "$sVHostRoot/css"
chmod -f 775 "$sVHostRoot/docs"
chmod -fR 750 "$sVHostRoot/files"
chmod -f 775 "$sVHostRoot/img"
chmod -f 775 "$sVHostRoot/includes"
chmod -f 775 "$sVHostRoot/js"
chmod -f 775 "$sVHostRoot/libs"
chmod -fR 750 "$sVHostRoot/logs"
chmod -f 750 "$sVHostRoot/media"
chmod -f 775 "$sVHostRoot/templates"
chmod -f 775 "$sVHostRoot/tests"
chmod -f 775 "$sVHostRoot/configs/app.xml"
chmod -f 775 "$sVHostRoot/configs/contacts.xml"
chmod -f 775 "$sVHostRoot/configs/hosts.xml"
chmod -f 775 "$sVHostRoot/.htaccess"
chmod -f 775 "$sVHostRoot/config.php"
chmod -f 775 "$sVHostRoot/index.php"

# ownership
chown -f apache:webdev "$sVHostRoot"
chown -f apache:webdev "$sVHostRoot/classes"
chown -f apache:webdev "$sVHostRoot/cli"
chown -fR apache:webdev "$sVHostRoot/configs"
chown -f apache:webdev "$sVHostRoot/css"
chown -f apache:webdev "$sVHostRoot/docs"
chown -fR apache:webdev "$sVHostRoot/files"
chown -f apache:webdev "$sVHostRoot/img"
chown -f apache:webdev "$sVHostRoot/includes"
chown -f apache:webdev "$sVHostRoot/js"
chown -f apache:webdev "$sVHostRoot/libs"
chown -fR apache:webdev "$sVHostRoot/logs"
chown -f apache:webdev "$sVHostRoot/media"
chown -f apache:webdev "$sVHostRoot/templates"
chown -f apache:webdev "$sVHostRoot/tests"
chown -f apache:webdev "$sVHostRoot/configs/app.xml"
chown -f apache:webdev "$sVHostRoot/configs/contacts.xml"
chown -f apache:webdev "$sVHostRoot/configs/hosts.xml"
chown -f apache:webdev "$sVHostRoot/.htaccess"
chown -f apache:webdev "$sVHostRoot/config.php"
chown -f apache:webdev "$sVHostRoot/index.php"