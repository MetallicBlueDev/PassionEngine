/**
 * D�tection du javascript via cookie
 * 
 * @author Sebastien Villemain
 */
function javascriptEnabled(name){if(navigator.cookieEnabled){var cookie_name=name;if(document.cookie.indexOf(cookie_name+'=')<0){document.cookie=cookie_name+'='+escape(1);document.location.reload()}}}