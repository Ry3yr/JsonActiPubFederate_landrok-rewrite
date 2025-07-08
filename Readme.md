
# JsonActiPubFederate (landrok rewrite)

A quick (test) rewrite of [JsonActiPubFederate](https://codeberg.org/alceawisteria/JsonActiPubFederate)
 to comply with [landrok/PHP](https://github.com/landrok/activitypub)

 [OK] Webfinger: [(demo)](https://yusaao.com/.well-known/webfinger?resource=acct:yusaao@yusaao.com)  
 [OK] Outbox works: [(demo)](https://yusaao.com/yusaao/outbox) (uses dynanmic ids for status IDs via content hash)   
 [X] Federation
  
(You can install landrok via "composer require landrok/activitypub" or just drop in the "vendor.zip" from this repo)
