# Rest

The /wp-json/pz/v1 API. Every route checks a capability and a nonce. The one exception is the public enquiry form (`EnquiryController`), which visitors use: it checks a nonce, a honeypot and a rate limit instead. Subscription routes arrive in Phase 3.
