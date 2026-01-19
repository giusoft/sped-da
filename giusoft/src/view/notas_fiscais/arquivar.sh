#!/bin/bash
cd /tmp
rm nfe_wms.* -f
rm *.xml -f
rm *.pdf -f
zip -r nfe_wms nfe_wms/
#scp -B -P 2222 -i /opt/id_rsa /tmp/nfe.zip servidor@192.168.0.3:/net/temp 2>> /tmp/erro >> /tmp/erro
#umount /tmp/h
rm /tmp/nfe_wms/* -f

