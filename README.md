[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jbelien/OVH-Monitoring/build-status/master)

# OVH-Monitoring

OVH VPS/Cloud Monitoring via [OVH API](https://api.ovh.com/) using PHP.

## Installation

```
composer create-project jbelien/ovh-monitoring
```

## Configuration

### First step

Create credentials : <https://api.ovh.com/createToken/index.cgi?GET=/vps*&GET=/cloud*&GET=/status*>

### Second step

Create `monitoring.ini` file :

```
application_key    = your_application_key
application_secret = your_application_secret
endpoint           = ovh-eu
consumer_key       = your_consumer_key
```

## Docker

### First step

Build image from [GitHub](https://github.com/jbelien/OVH-Monitoring):
```
docker build --rm -t jbelien/ovh-monitoring https://github.com/jbelien/OVH-Monitoring.git
```

**OR**

Pull image from [Docker Hub](https://hub.docker.com/r/jbelien/ovh-monitoring/):
```
docker pull jbelien/ovh-monitoring
```

**Warning:** `monitoring.ini` file will be missing.

### Second step

Create `monitoring.ini` file (see [Configuration](#configuration)).

### Third step

Run Docker container with your `monitoring.ini` mount as volume:

```
docker run --rm -p 80:80 -v "$PWD/monitoring.ini:/var/www/html/monitoring.ini" jbelien/ovh-monitoring
```

### Fourth step

Go to http://myserver/ (using port `80`) where `myserver` is the IP address of your server to have a look a the monitoring tool.
