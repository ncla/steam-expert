#!/usr/bin/python
import os
import sys
import time
from ConfigParser import ConfigParser

import decorator
from pyvirtualdisplay import Display
from selenium import webdriver
from selenium.common.exceptions import WebDriverException, NoSuchElementException
from selenium.webdriver.common.by import By

DEBUG = True if '--debug' in sys.argv else False


def debug_println(text):
    if DEBUG:
        print text


def debug_print(text):
    if DEBUG:
        sys.stdout.write(text + ' ')


def get_time():
    from datetime import datetime
    return str(datetime.now().strftime('%Y-%m-%d %H:%M:%S'))


def static_vars(**kwargs):
    def decorate(func):
        for k in kwargs:
            setattr(func, k, kwargs[k])
        return func

    return decorate


def print_error(msg):
    print '{"error":"' + msg + '"}'


@static_vars(config=ConfigParser(), read=False)
def get_env(name):
    if not get_env.read:
        get_env.config.readfp(open(os.path.dirname(os.path.abspath(__file__)) + '/../../.env'))
        get_env.read = True
    return get_env.config.get('SETTINGS', name)


def die(msg, callback, *exception_types):
    @decorator.decorator
    def try_it(func, *fargs, **fkwargs):
        try:
            return func(*fargs, **fkwargs)
        except exception_types or Exception:
            print_error(msg)
            if callable(callback):
                callback()
            exit()

    return try_it


class Fox:
    def __init__(self):
        pass

    display = Display(visible=0, size=(320, 240))
    display.start()
    browser = webdriver.Firefox()

    @staticmethod
    def stop():
        Fox.display.stop()
        try:
            Fox.browser.quit()
        except OSError:
            pass

    @staticmethod
    def get(url):
        Fox.browser.get(url)
        debug_print(url)

    @staticmethod
    def plaintext(selector=None):
        try:
            if selector is None:
                return Fox.browser.find_element(By.TAG_NAME, 'body').text
            else:
                return Fox.browser.find_element(By.CLASS_NAME, selector).text
        except NoSuchElementException:
            return ''

    @staticmethod
    def js_click(by, selector):
        el = Fox.browser.find_element(by, selector)
        Fox.browser.execute_script('$(arguments[0]).click();', el)

    @staticmethod
    @static_vars(times=0)
    def wait_for_redirect(url, wait=0.5):
        if url not in Fox.browser.current_url:
            if Fox.wait_for_redirect.times < 50:
                Fox.wait_for_redirect.times += 1
                time.sleep(wait)
                Fox.wait_for_redirect(url)

    @staticmethod
    def wait_for_element(by, value):
        if None in (by, value):
            return False
        for i in range(0, 50):
            try:
                Fox.browser.find_element(by, value)
            except WebDriverException:
                time.sleep(1)
                continue
            return True
        return False

    @staticmethod
    def debug_screenshot(name):
        Fox.browser.save_screenshot('/tmp/' + name)
