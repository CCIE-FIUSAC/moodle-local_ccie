#!/bin/bash
if [ $# -lt 1 ] || [ "$1" == "-h" ] || [ "$1" == "--help" ]; then
    echo "" >&2
    echo "Usage: $0 [ -h | --help ]" >&2
    echo "       $0 lo" >&2
    echo "       $0 package" >&2
    echo "       $0 change" >&2
    echo "" >&2
    echo "This will transform the name of the specified file(s)." >&2
    echo "" >&2
    exit 0
fi
# versión del plugin
version=2015111402
# destino del instalador .zip
destino=/mnt/memo
if [[ $1 == "lo" ]]; then
  cd ~/E/src/
  cp -r moodle-local_ccie $destino
  mv $destino/{moodle-local_ccie,ccie}
  cd $destino/
  rm -rf ccie/.git ccie/.gitignore
  scp -r ccie elearningwww:/var/www/campus/local
  rm -rf ccie
elif [[ $1 == "package" ]]; then
  cd ~/E/src
  cp -r moodle-local_ccie $destino
  mv $destino/{moodle-local_ccie,ccie}
  cd $destino/
  rm -rf ccie/.git ccie/*.sh
  zip -r local_ccie_moodle28_$version.zip ccie
  rm -rf ccie
  thunar .
elif [[ $1 == "change" ]]; then
  cd ~/E/src
  sudo cp -r moodle-local_ccie/externallib.php /var/www/html/campus/local/ccie
fi
