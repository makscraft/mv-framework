<?php
header_remove('X-Powered-By');
Http::responseText($mv -> seo -> robots);