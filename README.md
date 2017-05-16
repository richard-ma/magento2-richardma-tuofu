# magento2-richardma-tuofu

## 系统需求
* Magento 2.1.2

## 安装
* 将目录复制到magento根目录下的对应位置
* cd 到magento根目录
* php ./bin/magento setup:upgrade
* chown www:www -R ./

## 目录结构
- /app/code/Richardma/Tuofu
    - /etc
        - module.xml 模块配置文件
        - adminhtml/
            - system.xml 后台菜单 `Store->Settings->Configuration->Sales->Payment Method`
    - registeration.php compose模块配置文件
