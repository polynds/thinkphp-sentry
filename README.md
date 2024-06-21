# THINKPHP_SENTRY
A Sentry access plugin written for thinpap.

用于在ThinkPHP6+中接入Sentry的扩展。

## 安装

~~~
composer require polynds/thinkphp-sentry
~~~

## 配置

安装后config目录下会自带sentry.php配置文件。


## THINKPHP SENTRY

<p align=""><code>THINKPHP SENTRY</code>是一个在ThinkPHP6+中接入Sentry的扩展，使用简单方便。</p>


### 功能特性

- [x] 导出为Word
- [ ] 导出PDF、Excel、TXT


### 环境
- PHP >= 7.4.0
- Thinkphp 5.5.0 ~ 9.*

### 安装

> 如果安装过程中出现`composer`下载过慢或安装失败的情况，请运行命令`composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/`把`composer`镜像更换为阿里云镜像。

首先需要安装`laravel`框架，如已安装可以跳过此步骤。如果您是第一次使用`laravel`，请务必先阅读文档 [安装 《Laravel中文文档》](https://learnku.com/docs/laravel/8.x/installation/9354) ！
```bash
composer create-project --prefer-dist laravel/laravel 项目名称 7.*
# 或
composer create-project --prefer-dist laravel/laravel 项目名称
```

安装`thinkphp-sentry`

```
composer require polynds/thinkphp-sentry
```

## 配置

安装后config目录下会自带sentry.php配置文件。


## 测试Sentry

```
php think sentry:test
```

### License

`thinkphp-sentry` is licensed under [The MIT License (MIT)](LICENSE).
