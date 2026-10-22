<?php
// Order cancellation confirmation.
 page_title('KONFIRMASI','Batalkan pesanan #TM-'.$id); echo '<section class="panel narrow"><p>Pesanan yang dibatalkan tidak akan diproses lebih lanjut.</p>'; action_form('cancel',$id,'Ya, batalkan pesanan','button danger'); echo '</section>'; 