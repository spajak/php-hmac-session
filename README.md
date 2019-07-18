# Simple PHP storage-less, HMAC hashed session

No ids, no storage, not locks.

# Features:
 - No server storage needed. Session is serialized and as a whole travels to the client (using a cookie header).
   Then the client sends it back with the next request.

 - Secure. Session is hashed with [HMAC](https://en.wikipedia.org/wiki/HMAC) message authentication code before
   sending to client. Thanks to this we are able to verify both the data integrity and the authentication of a message (session).
   Session is also stamped with an expire time (as a unix timestamp appended to the session data). Expired session
   is considered invalid and discarded, but a supplied message is still available for investigation.

 - Binary serialized. Session data is serialized to a binary string using one of the two popular binary serializers:
   `igbinary` and `msgpack` (both are optional, and are available as PHP extensions). Thanks to this serialized session
   data can be significantly smaller and serialization process is generally faster comparing to other serializers.
   Serialization falls back to text form (json) in case both binary serializers are not available.

 - Simple. Just one PHP class with a clean, explicit and easy to use interface.
