# wspr2sondehub

# Original author:
This work is extended version that can track more than one balloon. The original scripts and data
you may find at:
https://github.com/RoelKroes/wspr2sondehub

# about wspr2sondehub
wspr2sondehub is a simple program written in PHP to scrape the wspr database every 5 minutes for telemetry from High Altitude Balloons, decode this telemetry, log the telemetry in a local file and post the telemetry on amateur.sondehub.org
It is still very, very basic but it runs.

#supported trackers
So far, U4B, Traquito and Qrplabs trackers are supported unter 'traquito' name. Change last entry in settings.pho; 'tracker_type' => 'traquito' or 'tracker_type' => 'zachtek1'.
Note that for example Jetpack board has firmware qrplabs. ZachTek is just zachtek1. 
So far, I can't find any ICT tracker in use. So, the script is not yet optimized to ICT tracker.

# separate database for each balloon
Just set one or more balloons, there will be two .csv files. For example; 9a4ge-11_rawlog.csv and 9a4ge-11_spotslog.csv .


The program follows the protocol as described at: https://www.qrp-labs.com/flights/s4#protocol
Currently I use it for my own balloons. 

The program is written in PHP and can be installed on almost any computer.

Feel free to improve and use this program.

# php
You can find many webpages on how to install PHP on Windows and Unix.

Be sure that in your php.ini file, in the [curl] section, curl.cainfo points to the cacert.pem file that is a part of this repository.
Windows example:
curl.cainfo ="C:\php\cacert.pem" 

And that the "curl" extension is enabled.
Windows Example:
extension=curl

# settings.php
Edit your callsing for uploader_call, you may add more description, for example: "9A4GE-RPi 4".
There are three balloons as an example, change it to your need, or add more balloons, just pay attention
to the format of the script. Remove my exaples with your balloon description.


# Run the program
Run the program from the command line:
php wspr2sondehub
