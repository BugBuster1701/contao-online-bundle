# Contao-Online-Bundle

[![Latest Stable Version](https://poser.pugx.org/bugbuster/contao-online-bundle/v/stable.svg)](https://packagist.org/packages/bugbuster/contao-online-bundle)
![Contao Version](https://img.shields.io/badge/Contao-5.1-orange) ![Contao Version](https://img.shields.io/badge/Contao-4.13-orange)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/bugbuster/contao-online-bundle)
![GitHub issues](https://img.shields.io/github/issues/BugBuster1701/contao-online-bundle)
[![License](https://poser.pugx.org/bugbuster/contao-online-bundle/license.svg)](https://packagist.org/packages/bugbuster/contao-online-bundle)

[![Codacy Badge](https://app.codacy.com/project/badge/Grade/e3d0161b9fad4df8a9dab380f433a495)](https://www.codacy.com/manual/BugBuster1701/contao-online-bundle?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=BugBuster1701/contao-online-bundle&amp;utm_campaign=Badge_Grade)


## About 
### Contao 5.3 helper bundle

Use Version 1.3+

The Events LoginSuccessEvent, LogoutEvent and TerminateEvent are used to register the online status of the users (FE/BE).  
For this purpose, the ID, the time stamp and a hash (hash_hmac,sha256) are created in the table tl_online_session.

This Helper Bundle is still under development and will be used in the future by the bundles "LastLogin" and "BE_User_Online".

### Contao 4.13 helper bundle

Use Version 1.2.x

The hooks "postLogin", "postLogout" and "postAuthenticate" are used to register the online status of the users (FE/BE).  
For this purpose, the ID, the time stamp and a hash (hash_hmac,sha256) are created in the table tl_online_session.

This Helper Bundle is still under development and will be used in the future by the bundles "LastLogin" and "BE_User_Online".
