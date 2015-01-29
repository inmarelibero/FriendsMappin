FriendsMappin.com
=================

[FriendsMappin.com](http://friendsmappin.com) displays on a map the follower and followings of a Twitter account.

It's just a proof of concept, mantained by Emanuele Gaspari ([@inmarelibero](http://twitter.com/inmarelibero)).

Please leave any feedback by creating issue on github, pinging me at [@inmarelibero](http://twitter.com/inmarelibero) or writing me at inmarelibero[at]gmail.com.

### Why

The original idea was, for a given Twitter user, to display his followers and followings on a map, by:

- fetching all friends and followers via Twitter REST APIs
- geocoding the field __location__ of each user in order to obtain __latitude__ and __longitude__
- placing a marker which would group all users with the same location

So I started working on it with the tools I'm most familiar with: Symfony and some bundles, and Javascript (I chose Angular, even if I'm not so good at it).

Still I don't exactly know what this is useful for, if someone finds it, please tell me.

### Technologies

Technologies involved in this project are:

- [Symfony framework](http://symfony.com), and in particular [MisdGuzzleBundle](https://github.com/misd-service-development/guzzle-bundle), [IvoryGoogleMapBundle](https://github.com/egeloen/IvoryGoogleMapBundle), [BazingaGeocoderBundle](https://github.com/geocoder-php/BazingaGeocoderBundle)
- [Twitter REST APIs](https://dev.twitter.com/rest/public), with the purpose to verify if we can use the [Streaming APIs](https://dev.twitter.com/streaming/overview)
- [AngularJS](https://angularjs.org/)
- Google Maps APIs

### Source code
I decide to release publicly the source code, because if someone had realized this platform as experiment, I would be very glad to see inside.

### License

FriendsMappin is released under [MIT License](http://opensource.org/licenses/MIT), fell free to contribute, use, improve and do whatever comes in mind to you.