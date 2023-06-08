#!/bin/bash -eux

#!/bin/sh
i=0
while [ $i -ne 1000 ]
do
  i=$(($i+1))
  echo "$i"
  usleep 100
done
