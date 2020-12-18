<style data-inline>

  #tweet {
    width: 400px !important;
    margin:0 auto;
  }

  #tweet iframe {
    border: none !important;
    box-shadow: none !important;
  }

</style>

<div id="tweet" tweetID="<?= $tweetID ?>"></div>

<script sync src="https://platform.twitter.com/widgets.js" data-inline></script>

<script data-inline>

  window.onload = (function(){

    var tweet = document.getElementById("tweet");
    var id = tweet.getAttribute("tweetID");

    twttr.widgets.createTweet(
      id, tweet,
      {
        conversation : 'none',    // or all
        cards        : 'hidden',  // or visible
        linkColor    : '#cc0000', // default is blue
        theme        : 'light'    // or dark
      })
    .then (function (el) {
      el.contentDocument.querySelector(".footer").style.display = "none";
    });

  });

</script>