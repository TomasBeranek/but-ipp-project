#!/usr/bin/env python
import sys

#just a simulation
file = sys.argv[1][8:]
file = file[0:-3] + ".out"
with open(file, 'r') as fin:
    print(fin.read())

rc_file = file[0:-3] + "rc"
with open(rc_file, 'r') as fin:
    tmp = fin.read()
    exit(int(tmp))
