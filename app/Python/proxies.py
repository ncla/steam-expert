#!/usr/bin/python
import inspect
import json
import sys
from httplib import BadStatusLine

from selenium.common.exceptions import WebDriverException, NoSuchElementException
from selenium.webdriver.common.by import By

from helpers import DEBUG, get_time, debug_print
from proxy_helpers import *

ONE_PAGE = True if '--onepage' in sys.argv else False
SITE = False
if '--site' in sys.argv:
    SITE = sys.argv[sys.argv.index('--site') + 1]


class Scraper:
    class ProxySiteHMA(object):
        def __init__(self):
            self._proxy_cont_selector = 'hma-table'
            self.urls = []
            pass

        @retry((BadStatusLine, WebDriverException), timeout=0.5)
        def loop(self):
            while self.urls:
                Fox.get(self.urls.pop(0))
                Proxies.from_plaintext(self._proxy_cont_selector)
                if ONE_PAGE:
                    return

        @retry(WebDriverException, timeout=0.5)
        def go(self):
            Fox.get('http://proxylist.hidemyass.com')
            Proxies.from_plaintext(self._proxy_cont_selector)
            links = Fox.browser.find_elements(By.CSS_SELECTOR, '.pagination li:not(.arrow):not(.current) a ')
            for link in links:
                self.urls.append(link.get_attribute('href'))
            self.loop()

    def run(self):
        methods = dir(self)
        for method in methods:
            if (method.startswith('ProxySite') and not SITE) or (SITE and method.endswith(SITE)):
                cur_proxy_count = len(Proxies.list)

                start_time = get_time()

                try:
                    debug_println('\nstarted ' + method)
                    getattr(getattr(self, method)(), 'go')()
                except:
                    Proxies.errors.append(method + ': uncaught exception ' + sys.exc_info()[0])

                end_time = get_time()
                new_proxy_count = len(Proxies.list) - cur_proxy_count

                if not new_proxy_count:
                    Proxies.errors.append(method + ' returned 0 proxies')

                self.perf_logs[method] = {'start_time': start_time, 'end_time': end_time, 'count': new_proxy_count}

        return self.perf_logs

    def __init__(self):
        self.perf_logs = {}
        pass


def main():
    Fox.browser.delete_all_cookies()
    start_time = get_time()
    perf_logs = Scraper().run()
    if len(Proxies.list):
        if not DEBUG:
            Proxies.insert()
    else:
        Proxies.errors.append('Got 0 proxies')

    finish_time = get_time()
    data = {'start_time': start_time, 'finish_time': finish_time, 'proxy_count': len(Proxies.list),
            'perf_logs': perf_logs, 'errors': Proxies.errors, "duplicates": Proxies.duplicates}
    print(json.dumps(data))


if __name__ == '__main__':
    try:
        main()
    except Exception as e:
        Proxies.errors.append(e.message)
        print json.dumps({'errors': Proxies.errors})
    finally:
        Fox.stop()
