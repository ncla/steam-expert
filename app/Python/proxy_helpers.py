import MySQLdb
import time
from httplib import IncompleteRead
from re import findall

import decorator

from helpers import Fox, get_env, debug_println


class Proxies(object):
    list = {}
    errors = []
    duplicates = 0

    @staticmethod
    def add(proxy):
        key = MySQLdb.escape_string(proxy[0].encode("ascii"))
        if key in Proxies.list:
            Proxies.duplicates += 1
        else:
            if key not in ('0.0.0.0', '127.0.0.1'):
                Proxies.list[key] = MySQLdb.escape_string(proxy[1].encode("ascii"))

    @staticmethod
    def insert():
        try:
            cnx = MySQLdb.connect(user=get_env('DB_USERNAME'), passwd=get_env('DB_PASSWORD'),
                                  host=get_env('DB_HOST'), db=get_env('DB_DATABASE'))
        except Exception as err:
            Proxies.errors.append('Failed to connect to DB: ' + str(err))
            return

        c = cnx.cursor()

        sql = 'INSERT INTO `proxies` (`ip`, `port`) VALUES'

        for proxy in list(Proxies.list.items()):
            sql += (' ("%s", "%s"),' % (proxy[0], proxy[1]))  # these have been sanitized earlier don't worry

        sql = sql[:-1] + ' ON DUPLICATE KEY UPDATE port = VALUES(port);'

        try:
            c.execute(sql[:-1])
            cnx.commit()
        except Exception as err:
            Proxies.errors.append('Failed to run query: ' + str(err))

    @staticmethod
    def from_els(ips, ports):
        try:
            debug_println(str(len(ips)) + ' proxies added')
            for ip, port in zip(ips, ports):
                Proxies.add([ip.text, port.text])
        except IncompleteRead:
            pass

    @staticmethod
    def from_inverted_plaintext():
        inverse_matches = findall('(\d+)(\s+)(\d+\.\d+\.\d+\.\d+)', Fox.plaintext(), flags=0)
        debug_println(str(len(inverse_matches)) + ' proxies added')
        for match in inverse_matches:
            Proxies.add([match[2], match[0]])

    @staticmethod
    def from_plaintext(selector=None, override=None):
        pt = Fox.plaintext(selector) if not override else override
        matches = findall('(\d+\.\d+\.\d+\.\d+)(\s+)(\d+)', pt, flags=0)
        matches = matches + findall('(\d+\.\d+\.\d+\.\d+)(:)(\d+)', pt, flags=0)
        for match in matches:
            Proxies.add([match[0], match[2]])


# thanks dwc @ SO for this function
def retry(*exception_types, **kwargs):
    timeout = kwargs.get('timeout', 0.0)  # seconds

    @decorator.decorator
    def try_it(func, *fargs, **fkwargs):
        for _ in xrange(3):
            try:
                return func(*fargs, **fkwargs)
            except exception_types or Exception:
                if timeout is not None:
                    time.sleep(timeout)
        Proxies.errors.append('Failed ' + str(3) + ' times: ' + str(fargs) + str(fkwargs))

    return try_it


data_tables_show_all_script = """
var desa = document.getElementsByTagName('span');
z = [];
for(var i in desa){
    d = desa [i];
    if(/\d/.test(d.className) && d.className){
        z.push([d.innerHTML.slice(0, -1),
        window.getComputedStyle(document.querySelector('.' + d.className),':after').getPropertyValue('content').replace('"', '').replace('"', '')]);
    }
}
return z;
"""
