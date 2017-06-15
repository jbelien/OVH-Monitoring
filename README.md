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

Create credentials : <https://api.ovh.com/createToken/index.cgi?GET=/vps*&GET=/cloud*&GET=/status*>

**Second step:**

Create `monitoring.ini` file (next to `public` directory) with your credentials :

```
application_key    = your_application_key
application_secret = your_application_secret
endpoint           = ovh-eu
consumer_key       = your_consumer_key
```

## Try with Docker

**First build image**

`monitoring.ini` files are ignored, see `.dockerignore`.

```
docker build --rm -t jbelien/ovh-monitoring .
```

**Then run**

With your monitoring.ini mount as volume.

```
docker run --rm -p 80:80 -v "$PWD/monitoring.ini.jeci:/var/www/html/monitoring.ini" jbelien/ovh-monitoring
```
