<?php
// Footer dosyası
$mesaj = null;
if (function_exists('mesajOku')) {
    $mesaj = mesajOku();
}
?>

    </div> <!-- .flex-1 -->
</div> <!-- .flex -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php if ($mesaj): ?>
<script>
    alert('<?php echo addslashes($mesaj['text']); ?>');
</script>
<?php endif; ?>

</body>
</html>