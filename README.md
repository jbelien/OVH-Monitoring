# OVH-Monitoring
OVH VPS/Cloud Monitoring via [OVH API](https://api.ovh.com/) using PHP.

## Configuration
**First step:**

Create credentials : <https://api.ovh.com/createToken/index.cgi?GET=/vps*&GET=/cloud*>

**Second step:**

Create `monitoring.ini` file (next to `monitoring.php` file) with your credentials :

```
application_key    = your_application_key
application_secret = your_application_secret
endpoint           = ovh-eu
consumer_key       = your_consumer_key
```

## Installation
```
composer require jbelien/ovh-monitoring
```
