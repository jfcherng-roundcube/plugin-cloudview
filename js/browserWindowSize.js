/**
 * @version $Id$
 * finding the size of the browser window
 * @author Rene Kanzler <rk (at) cosmomill (dot) de>
 */

var scrWidth = 0,
  scrHeight = 0;
if (typeof window.innerWidth == 'number') {
  // Non-IE ##
  scrWidth = window.innerWidth;
  scrHeight = window.innerHeight;
} else if (
  document.documentElement &&
  (document.documentElement.clientWidth || document.documentElement.clientHeight)
) {
  // IE 6+ in 'standards compliant mode' ##
  scrWidth = document.documentElement.clientWidth;
  scrHeight = document.documentElement.clientHeight;
} else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
  // IE 4 compatible ##
  scrWidth = document.body.clientWidth;
  scrHeight = document.body.clientHeight;
}
