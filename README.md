Example:
```
$ qb
Array
(
    [description] => King James Version (Red Letter)
    [abbreviation] => KJVR
    [comments] => This is the King James Version of the Holy Bible (1850 red letter revision).
    [font] => DEFAULT
    [format] => rtf
    [strongs] => 0
)
$ qb kjvr Jesus Wept
Joh 11:35 Jesus wept.

$ qb kjvr Psa 1
1  Blessed is the man that walketh not in the counsel of the ungodly, nor standeth in the way of sinners, nor sitteth in the seat of the scornful.
2 But his delight is in the law of the LORD; and in his law doth he meditate day and night.
3  And he shall be like a tree planted by the rivers of water, that bringeth forth his fruit in his season; his leaf also shall not wither; and whatsoever he doeth shall prosper.
4 The ungodly are not so: but are like the chaff which the wind driveth away.
5  Therefore the ungodly shall not stand in the judgment, nor sinners in the congregation of the righteous.
6 For the LORD knoweth the way of the righteous: but the way of the ungodly shall perish.

$ qb kjvr Joh 14:6
6  Jesus saith unto him, I am the way, the truth, and the life: no man cometh unto the Father, but by me.

```

Prerequisites
 1. PHP with pdo_sqlite mod enabled (Windows: in php.ini add extension=ext/php_pdo_sqlite.dll, on debian apt-get install php5-sqlite) 
 2. On Windows: Git Bash for Windows (mingw might work)

Instructions on Windows:
 1. Open your ~/.bashrc file, add PATH=$PATH:/c/util/apps/php-5.6.16-nts-Win32-VC11-x86/ or wherever your PHP folder is located.
 
Further Instructions
 1. You may copy everything to the /usr/bin/ directory so you may use qb from the command line 

Usage:
```qb Jesus Wept```
```qb Joh 3:16```
```qb```




