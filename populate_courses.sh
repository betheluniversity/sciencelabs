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

# Query for all coursecodes active in this semester
active_course_codes=$(
mysql -s -N -u $parameters__database_user --password=$parameters__database_password << EOF
USE $parameters__database_name;
SELECT GROUP_CONCAT(CONCAT(dept, ',', courseNum) SEPARATOR ' ')
FROM CourseCode
WHERE active = 1;
EOF
)

log_prefix=$parameters__database_name`echo _populate_courses`
log_file=$SCRIPTPATH/$parameters__log_root$log_prefix`echo '.log'`
printf "Course Population log for `date +%m-%d-%Y`\n====================================\n\n" > $log_file

course_code_array=($active_course_codes)
# Validate each course code and pull in courses from Banner
for i in ${course_code_array[@]}; do
    IFS=','
    course_code=($i)
    unset IFS
    dept=${course_code[0]}
    cnum=${course_code[1]}
    printf "\n========\n$dept $cnum\n========\n" >> $log_file
    php $SCRIPTPATH/app/console bethel:coursecode:create --populate-courses $dept $cnum >> $log_file 2>&1
done

cat $log_file

unset dept
unset cnum
unset logfile
