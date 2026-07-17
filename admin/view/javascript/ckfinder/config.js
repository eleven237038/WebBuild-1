/*
 Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.txt or http://cksource.com/ckfinder/license
 */

var config = {};

// Set your configuration options below.

function getCookie(cookie_name)
{
    if (document.cookie.length>0){
        c_start=document.cookie.indexOf(cookie_name + "=")
        if (c_start!=-1){
            c_start=c_start + cookie_name.length+1
            c_end=document.cookie.indexOf(";",c_start)
            if (c_end==-1) c_end=document.cookie.length
            return unescape(document.cookie.substring(c_start,c_end))
        }
    }
    return ""
}

var lang = getCookie('folder_language');
var language = '';
if (lang && lang == 'en-gb') {
    language = 'en';
} else{
    language = 'zh-cn';
}
// Examples:
config.language = language;//en, zh-cn
// config.skin = 'jquery-mobile';

CKFinder.define( config );
