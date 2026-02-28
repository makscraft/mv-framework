<?php
header_remove('X-Powered-By');
header_remove('Set-Cookie');
Http::responseText($mv -> seo -> robots);