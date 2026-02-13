/**
 * fix onload animation bug
 */
const fixChromeLoadingPageAnimationIssue = function () {
    document.body.classList.remove("preload");
};
window.addEventListener("DOMContentLoaded", fixChromeLoadingPageAnimationIssue);
