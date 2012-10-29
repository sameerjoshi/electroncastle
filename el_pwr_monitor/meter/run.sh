#!/bin/bash

# Get absolute path to this script 
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Run the power meter app
cd ${DIR}
python elmeter.py

