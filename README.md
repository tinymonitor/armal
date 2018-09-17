# ARMAL
## Automatic Real-time Monitor And Log

This is a simple PHP script to analyze web traffic. It analyzes and logs real-time web visitors: UTC time of visit, country, IP address and user agent of the web client. It is implemented as a standalone page, however, you will very likely modify it according to your needs or include it into your project.

The script can also be used to detect web bots, spiders and crawlers.

![ARMAL screenshot](/images/screenshot.png)

No configuration is needed. You may want to define admin IP address in the `$admin_ip` variable. These addresses are excluded from monitoring.

The log is stored as a serialized array for easier processing. You can define how many entries are saved into the log and how many entries are displayed.

Happy monitoring and logging!
