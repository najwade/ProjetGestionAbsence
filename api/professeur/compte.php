<?php 
	declare(strict_types=1);
	namespace Professeur;
	use \Tools\DB;
	use \Tools\U;
	class Compte{
		private static function _get_account(DB &$db,&$email){
			$q="
				select 
					cp.mot_de_passe as mot_de_passe,
					cp.id as compte_id,
					p.id as id,
					p.nom_complet as nom_complet,
					p.email as email
				from
					compte_professeur cp
					inner join professeur p on cp.professeur_id=p.id
				where 
					p.email=?
				limit
					1
			";
			$r=$db->stmt_exec_select($q,[$email])->fetchObject();
			if (!$r)
				return [false,'not found'];
			return [true,$r];
		}
		public static function connect($id,$compte_id,$email){
			return DB::no_trans(function(DB &$db) use (&$id,&$compte_id,&$email){
				list($ok,$r)=$res=self::_get_account($db,$email);
				if (!$ok)
					return $res;
				if ($r->compte_id!=$compte_id || $r->id!=$id)
					return [false,'login is required'];
				return $res;
			});
		}
		public static function login($email,$mot_de_passe){
			return DB::no_trans(function(DB &$db) use (&$email,&$mot_de_passe){
				list($ok,$r)=$res=self::_get_account($db,$email);
				if (!$ok)
					return $res;
				if (!password_verify($mot_de_passe,$r->mot_de_passe))
					return [false,'email ou mot de passe est incorrect'];
				return [true,[
					'id'=>$r->id,
					'compte_id'=>$r->compte_id,
					'nom_complet'=>$r->nom_complet,
					'session'=>Session::save($r->id,$r->compte_id,$email)
				]];
			});
		}
		public static function registry($email,$mot_de_passe){
			return DB::trans(function(DB &$db) use (&$email,&$mot_de_passe){
				list($ok,$r)=self::_get_account($db,$email);
				if ($ok)
					return [false,'email deja utilisé'];
				$q="select * from professeur where email=?";
				$r=$db->stmt_exec_select($q,[$email])->fetchObject();
				if (!$r)
					return [false,'email is incorrect'];
				$q="insert into compte_professeur (professeur_id,mot_de_passe) values(?,?)";
				$s=$db->stmt_exec_affect($q,[$r->id,password_hash($mot_de_passe,PASSWORD_DEFAULT)],1);
				return [true];
			});
		}
		public static function logout(){
			\Shared\Session::unsave();
			return [true];
		}
		public static function api(){
			return U::do_try(function(){
				$a=U::assert_g_in('action','login','registry','logout');
				if ($a=='logout')
					return self::logout();
				$email=U::assert_p_email('email');
				$mot_de_passe=U::assert_p_pass('mot_de_passe');
				if ($a=='login')
					return self::login($email,$mot_de_passe);
				else{
					$mot_de_passe2=U::assert_p_pass('mot_de_passe2');
					if ($mot_de_passe!=$mot_de_passe2)
						return [false,"confirmation de mot de passe es t incorrect"];
					return self::registry($email,$mot_de_passe);
				}
			});
		}

	}
/*
tb_static : etudiant
	id,code_apogee,email,nom_complet,groupe_id 

tb_static : professeur 
	id,email,nom_complet 

tb_static : groupe
	id,code

tb_static : module 
	id,module


tb_static : groupe_emploie
	id,groupe_id,module_id,prof_id,jour,debut,fin

------- needs more think
tb (auto generated) : education:
	id,groupe_emploie_id,date

tb : absence
	id,education_id,statut

select 
	emploie.jour as jour,
    module.module as module,
    groupe.code as groupe,
    emploie.debut as debut,
    emploie.fin as fin
from 
	emploie
    inner join professeur on professeur.id=emploie.professeur_id
    inner join groupe on groupe.id=emploie.groupe_id
    inner join module on module.id=emploie.module_id
where 
	professeur.email='rachid.sabiri19@ump.ac.ma'



absence_list
	id,date,emploie_id

abscence 
	id, etudiant_id,absence_list_id,statut



*/
?>