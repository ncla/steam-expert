import os

from proxy_helpers import *

for filename in os.listdir('./proxies'):
    with open('./proxies/' + filename, 'r') as proxyFile:
        parse_plain_text_for_proxies(override=proxyFile.read())

insert_proxies()
