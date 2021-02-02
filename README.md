# php调用apollo SDK

## 目录结构
```
./
├── src         源码
├── tests       测试用例
└── vendor      扩展库

```
## 开始使用

* 在根目录新建文件auth.json

```php

{
  "bitbucket-oauth": {},
  "github-oauth": {},
  "gitlab-oauth": {},
  "gitlab-token": {
    "git.papamk.com": "htmbUsQxA284wm3VdFCL"
  },
  "http-basic": {},
  "gitlab-domains":["gitlab.d1-zhan.com:8081"]
}

```

* 引入Composer包

* ccomposer.json文件中加入以下代码

```php
"require": {
        "tofun/apollo-public-sdk": "dev-master"
    },
```
```php
"repositories": {
        "apollo-public-sdk": {
            "type": "gitlab",
            "url": "http://gitlab.d1-zhan.com:8081/tofun/apollo-public-sdk.git"
        }
    }
```

* 执行以下命令
```bash
composer require tofun/apollo-public-sdk
```
