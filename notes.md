sudo ps -e |  grep -i chromedriver_mac | grep -v grep | awk '{ print $1 }
sudo ps -e |  grep -i chromedriver_mac | grep -v grep | awk '{ print $1 } | xargs kill -15
/Users/matheusfaustino/Documents/Projects/poc-panther-promise/vendor/symfony/panther/chromedriver-bin/chromedriver_mac64 --headless    window-size=1200,1100    --disable-gpu    --port=9999    --no-sandbox






https://github.com/amphp/artax/issues/174
