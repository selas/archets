<?php 

$lieu = get_post_meta(get_the_ID(), 'wpcf-lieu', true);
$musiciens = get_post_meta(get_the_ID(), 'wpcf-musiciens', true);
$beneficiaire = get_post_meta(get_the_ID(), 'wpcf-beneficiaire', true);
$recette = get_post_meta(get_the_ID(), 'wpcf-recette', true);

$output ='';

?>

<div class = "meta">

	<?php if(!empty($lieu)){
		$output = $output.'<p> Lieu : '.$lieu.'</p>';
	}

	if(!empty($recette)){
		$output = $output.'<p> Recette : '.$recette.'</p>';
	}

	if(!empty($musiciens)){
		$output = $output.'<p> Musicien(s) : '.$musiciens.'</p>';
	}

	if(!empty($beneficiaire)){
		$output = $output.'<p> Bénéficiaire(s) : '.$beneficiaire.'</p>';
	}

	?>

	<span><?php echo $output; ?></span>
		
</div>
