[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/build-status/master)

# OVH-Monitoring

OVH VPS/Cloud Monitoring via [OVH API](https://api.ovh.com/) using PHP.

## Installation

```
composer create-project jbelien/ovh-monitoring
```

## Configuration

**First step:**

Create credentials : <https://api.ovh.com/createToken/index.cgi?GET=/vps*&GET=/cloud*>

**Second step:**

Create `monitoring.ini` file (next to `public` directory) with your credentials :

```
application_key    = your_application_key
application_secret = your_application_secret
endpoint           = ovh-eu
consumer_key       = your_consumer_key
```
