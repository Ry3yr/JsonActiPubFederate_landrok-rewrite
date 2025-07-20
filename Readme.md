# JsonActiPubFederate (landrok rewrite)

Targeting PHP version: 8.3.6. 

--Required: --  
•  dependencies: "sudo apt install php php-cli php-curl php-mbstring php-xml php-json php-zip php-openssl"  
• Domain with SSL (A set to your target server and server configured vice versa) - LetsEncrypt works  
• RewriteRules must usable (.htaccess). [This is NOT optional!].  
• should run from /www (index.php will reroute most connections, check debug.log and fetch popcorn)  


A quick (test) rewrite of [JsonActiPubFederate](https://codeberg.org/alceawisteria/JsonActiPubFederate)
 to comply with [landrok/PHP](https://github.com/landrok/activitypub)

 [OK] Webfinger: [(demo)](https://alceawis.com/.well-known/webfinger?resource=acct:alceawis@alceawis.com)   [(ex)](https://yusaao.com/.well-known/webfinger?resource=acct:yusaao@yusaao.com)  
 [OK] Outbox works: [(demo)](https://alceawis.com/alceawis/outbox?page=true) [(ex)](https://yusaao.com/yusaao/outbox?page=true) (uses dynanmic ids for status IDs via content hash)   
 [OK] Federation: Following  [it](https://alceawis.com/)  works. Replies and likes are logged to interaction.json. 
  => Main page: Will show posts (aswell as likes/replies to them) and redirect status urls to a rendered state.  

(You can install landrok via "composer require landrok/activitypub" or just drop in the "vendor.zip" in extracted form from this repo)
