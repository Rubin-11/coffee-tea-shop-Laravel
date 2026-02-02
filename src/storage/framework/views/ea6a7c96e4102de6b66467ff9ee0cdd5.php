<?php $__env->startSection('scripts'); ?>
<script>
    alert('This is an alert from the abouts page.');
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <section>
        <h2>About Us</h2>
        <p>This is a simple HTML an CSS template to start your project.</p>

        <p>Name: <?php echo e($name); ?></p>
        <p>Id: <?php echo e($id); ?></p>
    </section>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/abouts.blade.php ENDPATH**/ ?>