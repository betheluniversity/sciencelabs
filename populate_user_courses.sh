function parse_yaml {
    local prefix=$2
    local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
    sed -ne "s|^\($s\):|\1|" \
        -e "s|^\($s\)\($w\)$s:$s[\"']\(.*\)[\"']$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  $1 |
    awk -F$fs '{
        indent = length($1)/2;
        vname[indent] = $2;
        for (i in vname) {if (i > indent) {delete vname[i]}}
        if (length($3) > 0) {
            vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
            printf("%s%s%s=\"%s\"\n", "'$prefix'",vn, $2, $3);
        }
    }'
}

#!/bin/bash
# Absolute path to this script, e.g. /home/user/bin/foo.sh
SCRIPT=$(readlink -f "$0")
# Absolute path this script is in, thus /home/user/bin
SCRIPTPATH=$(dirname "$SCRIPT")
eval $(parse_yaml $SCRIPTPATH/app/config/parameters.yml)

# Query for all students active in this semester
student_usernames=$(
mysql -s -N -u $parameters__database_user --password=$parameters__database_password << EOF
USE $parameters__database_name;
SELECT GROUP_CONCAT(username SEPARATOR ' ') FROM User
JOIN user_role ON User.id = user_id
JOIN Role ON role_id = Role.id
WHERE role = 'ROLE_STUDENT'
AND User.deletedAt IS NULL;
EOF
)

log_prefix=$parameters__database_name`echo _populate_user_courses`
log_file=$SCRIPTPATH/$parameters__log_root$log_prefix`echo '.log'`
printf "User Course Population log for `date +%m-%d-%Y`\n=========================================\n\n" > $log_file

username_array=($student_usernames)
# Pull in course enrollment data from Banner for each student
for username in ${username_array[@]}; do
    php $SCRIPTPATH/app/console bethel:user:courses $username >> $log_file 2>&1
done

cat $log_file

unset username
unset log_file
