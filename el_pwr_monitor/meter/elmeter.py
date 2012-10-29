# Copyright 1997-2012 Jiri Fajtl
# This code is released under the MIT License
# http://www.opensource.org/licenses/mit-license.php

# Dependencies:
#
# Requests
# http://docs.python-requests.org/en/latest/
# Tested with version 0.10.8 but should work with the latest one as well.
#
# Serial
# http://pyserial.sourceforge.net/ 
# Tested with version 2.6, again the latest one should be ok as well.

# Application was tested with python 2.7.3

import sys
import time
import requests
from serial import * 

START_CMD=[0xAA, 0x00, 0x00, 0xAD]
READ1_CMD=[0xAA, 0x01, 0x00, 0xAD]
READ2_CMD=[0xAA, 0x02, 0x00, 0xAD]


def write_cmd(device, cmd):
    device.write(to_bytes(cmd))

def read_data(device):
    data=[]
    while True:
        b = device.read()
        # Read all available bytes
        if len(b) == 0:
            break
        data.append(b)        
    return data
    
def exec_cmd(device, cmd):
    write_cmd(device, cmd)
    # The power meeter needs some time to deal with the command
    time.sleep(0.2)
    return read_data(device)
        
# Extract the current power consumption in Watts from the data
# returned by the power meter        
def decode_power(data):
    power = 0
    data_len = len(data)
    if (data_len > 70):
        power = ord(data[70]) * 256 + ord(data[69])
    return power            
        
# Returns current power in watts        
def read_power(device):
    data = exec_cmd(device, READ1_CMD)
    data = exec_cmd(device, READ2_CMD)    
    return decode_power(data)            


# Main function that initializes the power meter and then
# periodically reads the current power consumption and prints 
# it out on the standard output. If a web address with a correct  
# path is specified in the webapp parameter it will also execute
# an http GET request with the power information appended to the 
# web address string. Period parameter specifies a delay between
# measurements in seconds.
def run(uart_port, webapp="", period_sec=4): 
       
    # Open the serial line
    device = serial_for_url(uart_port, timeout=1)
 
    # Initialize the device
    data = exec_cmd(device, START_CMD)    
    print "Startup response: ",
    print data
    
    # Keep reading the power consumption infinitely
    while True:
        power = read_power(device)
        print str(power) + " Watts   ",
        
        # Sometimes the power meter returns 0 Watts. This can happen if 
        # the power meter itself fails to read data from the main unit over the RF link.
        if power == 0:
            print "  Ignoring *********************************"
            continue

        # Upload on the server if possible   
        if webapp != "":               
            try:
                result = requests.get(webapp+str(power))
                print "Update request: "+str(result.status_code)
            except Exception as e:
                print "Connection error: ", e
            except:
                print "Unknown exception: ", sys.exc_info()[0]

        # Pause for 'period' seconds            
        time.sleep(period_sec)
                
    device.close()    


if __name__=="__main__":
    
    uart_port = "/dev/ttyUSB1"
    webapp = 'http://www.fajtl.net/el/power.php?token=a5K4Dli8L0&add='
    run(uart_port, webapp)

