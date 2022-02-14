#!/usr/bin/env bash

#--------------------------------------------------#
# This script compiles asset files.                #
#                                                  #
# Author: Jack Cherng <jfcherng@gmail.com>         #
#--------------------------------------------------#

SCRIPT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )
THREAD_CNT=$(getconf _NPROCESSORS_ONLN)
PROJECT_ROOT=${SCRIPT_DIR}

LESS_FILES=(
    "skins/classic/pages/mail.less"
    "skins/classic/pages/settings.less"
    "skins/elastic/pages/mail.less"
    "skins/elastic/pages/settings.less"
    "skins/larry/pages/mail.less"
    "skins/larry/pages/settings.less"
)

JS_FILES=(
    "assets/pages/mail.js"
    "assets/pages/settings.js"
)


#-------#
# begin #
#-------#

pushd "${SCRIPT_DIR}" || exit


#--------------------#
# compile LESS files #
#--------------------#

for file_src in "${LESS_FILES[@]}"; do
    if [ ! -f "${file_src}" ]; then
        echo "'${file_src}' is not a file..."
        continue
    fi

    echo "==================================="
    echo "Begin compile '${file_src}'..."
    echo "==================================="

    file_dst=${file_src%.*}.css

    npx lessc --insecure "${file_src}" \
        | printf "%s\n" "$(cat -)" \
        | npx cleancss -O2 -f 'breaks:afterAtRule=on,afterBlockBegins=on,afterBlockEnds=on,afterComment=on,afterProperty=on,afterRuleBegins=on,afterRuleEnds=on,beforeBlockEnds=on,betweenSelectors=on;spaces:aroundSelectorRelation=on,beforeBlockBegins=on,beforeValue=on;indentBy:2;indentWith:space;breakWith:lf' \
        > "${file_dst}"
done


#----------------------------#
# transpile Javascript files #
#----------------------------#

for file_src in "${JS_FILES[@]}"; do
    if [ ! -f "${file_src}" ]; then
        echo "'${file_src}' is not a file..."
        continue
    fi

    echo "==================================="
    echo "Begin transpile '${file_src}'..."
    echo "==================================="

    file_export=${file_src%.*}.export.js
    file_dst=${file_src%.*}.min.js

    if [ ! -f "${file_export}" ]; then
        has_no_file_export=true
        echo ";" > "${file_export}"
    fi

    # to make the output file more diff-friendly, we beautify it and remove leading spaces
    cat "${file_src}" "${file_export}" \
        | npx browserify -t [ babelify ] - \
        | npx terser --config-file terser.json -- \
        | sed -e 's/[[:space:]]+$//' \
        > "${file_dst}"

    if [ "${has_no_file_export}" = "true" ]; then
        rm -f "${file_export}"
    fi
done


#-----#
# end #
#-----#

popd || exit
