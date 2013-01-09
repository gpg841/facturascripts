<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'model/balance.php';
require_once 'model/cuenta.php';
require_once 'model/ejercicio.php';
require_once 'model/epigrafe.php';
require_once 'model/subcuenta.php';

class contabilidad_ejercicio extends fs_controller
{
   public $ejercicio;
   public $importar_url;
   public $listado;
   public $listar;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('contabilidad_ejercicio', 'Ejercicio', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      if( isset($_POST['codejercicio']) )
      {
         $this->ejercicio = new ejercicio();
         $this->ejercicio = $this->ejercicio->get($_POST['codejercicio']);
         if($this->ejercicio)
         {
            $this->ejercicio->nombre = $_POST['nombre'];
            $this->ejercicio->fechainicio = $_POST['fechainicio'];
            $this->ejercicio->fechafin = $_POST['fechafin'];
            $this->ejercicio->estado = $_POST['estado'];
            if( $this->ejercicio->save() )
               $this->new_message('Datos guardados correctamente.');
            else
               $this->new_error_msg('Imposible guardar los datos.');
         }
      }
      else if( isset($_GET['cod']) )
      {
         $this->ejercicio = new ejercicio();
         $this->ejercicio = $this->ejercicio->get($_GET['cod']);
      }
      else
         $this->ejercicio = FALSE;
      
      if($this->ejercicio)
      {
         if( isset($_GET['export']) )
            $this->exportar_xml();
         else
         {
            $this->ppage = $this->page->get('contabilidad_ejercicios');
            $this->page->title = $this->ejercicio->codejercicio.' ('.$this->ejercicio->nombre.')';
            $this->buttons[] = new fs_button('b_importar', 'importar');
            $this->buttons[] = new fs_button('b_exportar', 'exportar',
                    $this->url().'&export=TRUE', '', 'img/tools.png', '*', TRUE);
            
            /// comprobamos el proceso de importación
            $this->importar_xml();
            
            if( isset($_GET['offset']) )
               $this->offset = intval($_GET['offset']);
            else
               $this->offset = 0;
            
            if( !isset($_GET['listar']) )
               $this->listar = 'cuentas';
            else if($_GET['listar'] == 'grupos')
               $this->listar = 'grupos';
            else if($_GET['listar'] == 'epigrafes')
               $this->listar = 'epigrafes';
            else if($_GET['listar'] == 'subcuentas')
               $this->listar = 'subcuentas';
            else
               $this->listar = 'cuentas';
            
            switch($this->listar)
            {
               default:
                  $cuenta = new cuenta();
                  $this->listado = $cuenta->full_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
               
               case 'grupos';
                  $ge = new grupo_epigrafes();
                  $this->listado = $ge->all_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
               
               case 'epigrafes':
                  $epigrafe = new epigrafe();
                  $this->listado = $epigrafe->all_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
               
               case 'subcuentas':
                  $subcuenta = new subcuenta();
                  $this->listado = $subcuenta->all_from_ejercicio( $this->ejercicio->codejercicio );
                  break;
            }
         }
      }
      else
         $this->new_error_msg('Ejercicio no encontrado.');
   }
   
   public function version()
   {
      return parent::version().'-2';
   }
   
   public function url()
   {
      if( $this->ejercicio )
         return $this->ejercicio->url();
      else
         return parent::url();
   }
   
   private function exportar_xml()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      /// creamos el xml
      $cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : ejercicio_".$this->ejercicio->codejercicio.".xml
    Description:
        Estructura de grupos de epígrafes, epígrafes, cuentas y subcuentas del ejercicio ".
      $this->ejercicio->codejercicio.".
-->

<ejercicio>
</ejercicio>\n";
      $archivo_xml = simplexml_load_string($cadena_xml);
      
      /// añadimos los balances
      $balance = new balance();
      foreach($balance->all() as $ba)
      {
         $aux = $archivo_xml->addChild("balance");
         $aux->addChild("codbalance", $ba->codbalance);
         $aux->addChild("naturaleza", $ba->naturaleza);
         $aux->addChild("nivel1", $ba->nivel1);
         $aux->addChild("descripcion1", base64_encode($ba->descripcion1) );
         $aux->addChild("nivel2", $ba->nivel2);
         $aux->addChild("descripcion2", base64_encode($ba->descripcion2) );
         $aux->addChild("nivel3", $ba->nivel3);
         $aux->addChild("descripcion3", base64_encode($ba->descripcion3) );
         $aux->addChild("orden3", $ba->orden3);
         $aux->addChild("nivel4", $ba->nivel4);
         $aux->addChild("descripcion4", base64_encode($ba->descripcion4) );
         $aux->addChild("descripcion4ba", base64_encode($ba->descripcion4ba) );
      }
      
      /// añadimos las cuentas de balances
      $balance_cuenta = new balance_cuenta();
      foreach($balance_cuenta->all() as $ba)
      {
         $aux = $archivo_xml->addChild("balance_cuenta");
         $aux->addChild("codbalance", $ba->codbalance);
         $aux->addChild("codcuenta", $ba->codcuenta);
         $aux->addChild("descripcion", base64_encode($ba->desccuenta) );
      }
      
      /// añadimos las cuentas de balance abreviadas
      $balance_cuenta_a = new balance_cuenta_a();
      foreach($balance_cuenta_a->all() as $ba)
      {
         $aux = $archivo_xml->addChild("balance_cuenta_a");
         $aux->addChild("codbalance", $ba->codbalance);
         $aux->addChild("codcuenta", $ba->codcuenta);
         $aux->addChild("descripcion", base64_encode($ba->desccuenta) );
      }
      
      /// añadimos las cuentas especiales
      $cuenta_esp = new cuenta_especial();
      foreach($cuenta_esp->all() as $ce)
      {
         $aux = $archivo_xml->addChild("cuenta_especial");
         $aux->addChild("idcuentaesp", $ce->idcuentaesp);
         $aux->addChild("descripcion", base64_encode($ce->descripcion) );
      }
      
      /// añadimos los grupos de epigrafes
      $grupo_epigrafes = new grupo_epigrafes();
      $grupos_ep = $grupo_epigrafes->all_from_ejercicio($this->ejercicio->codejercicio);
      foreach($grupos_ep as $ge)
      {
         $aux = $archivo_xml->addChild("grupo_epigrafes");
         $aux->addChild("codgrupo", $ge->codgrupo);
         $aux->addChild("descripcion", base64_encode($ge->descripcion) );
      }
      
      /// añadimos los epigrafes
      $epigrafe = new epigrafe();
      foreach($epigrafe->all_from_ejercicio($this->ejercicio->codejercicio) as $ep)
      {
         $aux = $archivo_xml->addChild("epigrafe");
         $aux->addChild("codgrupo", $ep->codgrupo);
         $aux->addChild("codepigrafe", $ep->codepigrafe);
         $aux->addChild("descripcion", base64_encode($ep->descripcion) );
      }
      
      /// añadimos las cuentas
      $cuenta = new cuenta();
      $num = 0;
      $cuentas = $cuenta->full_from_ejercicio($this->ejercicio->codejercicio);
      foreach($cuentas as $c)
      {
         $aux = $archivo_xml->addChild("cuenta");
         $aux->addChild("codepigrafe", $c->codepigrafe);
         $aux->addChild("codcuenta", $c->codcuenta);
         $aux->addChild("descripcion", base64_encode($c->descripcion) );
         $aux->addChild("idcuentaesp", $c->idcuentaesp);
      }
      
      /// añadimos las subcuentas
      $subcuenta = new subcuenta();
      foreach($subcuenta->all_from_ejercicio($this->ejercicio->codejercicio) as $sc)
      {
         $aux = $archivo_xml->addChild("subcuenta");
         $aux->addChild("codcuenta", $sc->codcuenta);
         $aux->addChild("codsubcuenta", $sc->codsubcuenta);
         $aux->addChild("descripcion", base64_encode($sc->descripcion) );
         $aux->addChild("coddivisa", $sc->coddivisa);
      }
      
      /// volcamos el XML
      header("content-type: application/xml; charset=UTF-8");
      echo $archivo_xml->asXML();
   }
   
   private function importar_xml()
   {
      $import_step = 0;
      $this->importar_url = FALSE;
      
      if( isset($_POST['archivo']) )
      {
         if( file_exists('tmp/ejercicio.xml') )
            unlink('tmp/ejercicio.xml');
         copy($_FILES['farchivo']['tmp_name'], 'tmp/ejercicio.xml');
         
         $import_step = 1;
         $this->importar_url = $this->url().'&importar='.(1 + $import_step);
      }
      else if( isset($_GET['importar']) )
      {
         $import_step = intval($_GET['importar']);
         if( $import_step < 5 )
            $this->importar_url = $this->url().'&importar='.(1 + $import_step);
         else
            $import_step = 0;
      }
      
      if( file_exists('tmp/ejercicio.xml') AND $import_step > 0 )
      {
         $this->new_message('Importando ejercicio: paso '.$import_step.' de 4 ...');
         
         $xml = simplexml_load_file('tmp/ejercicio.xml');
         if( $xml )
         {
            if( $xml->balance AND $import_step == 1 )
            {
               foreach($xml->balance as $b)
               {
                  $balance = new balance();
                  if( !$balance->get($b->codbalance) )
                  {
                     $balance->codbalance = $b->codbalance;
                     $balance->naturaleza = $b->naturaleza;
                     $balance->nivel1 = $b->nivel1;
                     $balance->descripcion1 = base64_decode($b->descripcion1);
                     $balance->nivel2 = $balance->intval($b->nivel2);
                     $balance->descripcion2 = base64_decode($b->descripcion2);
                     $balance->nivel3 = $b->nivel3;
                     $balance->descripcion3 = base64_decode($b->descripcion3);
                     $balance->orden3 = $b->orden3;
                     $balance->nivel4 = $b->nivel4;
                     $balance->descripcion4 = base64_decode($b->descripcion4);
                     $balance->descripcion4ba = base64_decode($b->descripcion4ba);
                     $balance->save();
                  }
               }
               
               if( $xml->balance_cuenta )
               {
                  $balance_cuenta = new balance_cuenta();
                  $all_bcs = $balance_cuenta->all();
                  foreach($xml->balance_cuenta as $bc)
                  {
                     $encontrado = FALSE;
                     foreach($all_bcs as $bc2)
                     {
                        if($bc2->codbalance == $bc->codbalance AND $bc2->codcuenta == $bc->codcuenta)
                        {
                           $encontrado = TRUE;
                           break;
                        }
                     }
                     if( !$encontrado )
                     {
                        $new_bc = new balance_cuenta();
                        $new_bc->codbalance = $bc->codbalance;
                        $new_bc->codcuenta = $bc->codcuenta;
                        $new_bc->desccuenta = base64_decode($bc->descripcion);
                        $new_bc->save();
                     }
                  }
               }
               
               if( $xml->balance_cuenta_a )
               {
                  $balance_cuenta_a = new balance_cuenta_a();
                  $all_bcas = $balance_cuenta_a->all();
                  foreach($xml->balance_cuenta_a as $bc)
                  {
                     $encontrado = FALSE;
                     foreach($all_bcas as $bc2)
                     {
                        if($bc2->codbalance == $bc->codbalance AND $bc2->codcuenta == $bc->codcuenta)
                        {
                           $encontrado = TRUE;
                           break;
                        }
                     }
                     if( !$encontrado )
                     {
                        $new_bc = new balance_cuenta_a();
                        $new_bc->codbalance = $bc->codbalance;
                        $new_bc->codcuenta = $bc->codcuenta;
                        $new_bc->desccuenta = base64_decode($bc->descripcion);
                        $new_bc->save();
                     }
                  }
               }
            }
            
            if( $import_step == 2 )
            {
               if( $xml->cuenta_especial )
               {
                  foreach($xml->cuenta_especial as $ce)
                  {
                     $cuenta_especial = new cuenta_especial();
                     if( !$cuenta_especial->get( $ce->idcuentaesp ) )
                     {
                        $cuenta_especial->idcuentaesp = $ce->idcuentaesp;
                        $cuenta_especial->descripcion = base64_decode($ce->descripcion);
                        $cuenta_especial->save();
                     }
                  }
               }
               
               if( $xml->grupo_epigrafes )
               {
                  foreach($xml->grupo_epigrafes as $ge)
                  {
                     $grupo_epigrafes = new grupo_epigrafes();
                     if( !$grupo_epigrafes->get_by_codigo($ge->codgrupo, $this->ejercicio->codejercicio) )
                     {
                        $grupo_epigrafes->codejercicio = $this->ejercicio->codejercicio;
                        $grupo_epigrafes->codgrupo = $ge->codgrupo;
                        $grupo_epigrafes->descripcion = base64_decode($ge->descripcion);
                        $grupo_epigrafes->save();
                     }
                  }
               }
               
               if( $xml->epigrafe )
               {
                  $grupo_epigrafes = new grupo_epigrafes();
                  foreach($xml->epigrafe as $ep)
                  {
                     $epigrafe = new epigrafe();
                     if( !$epigrafe->get_by_codigo($ep->codepigrafe, $this->ejercicio->codejercicio) )
                     {
                        $ge = $grupo_epigrafes->get_by_codigo($ep->codgrupo, $this->ejercicio->codejercicio);
                        if($ge)
                        {
                           $epigrafe->idgrupo = $ge->idgrupo;
                           $epigrafe->codgrupo = $ge->codgrupo;
                           $epigrafe->codejercicio = $this->ejercicio->codejercicio;
                           $epigrafe->codepigrafe = $ep->codepigrafe;
                           $epigrafe->descripcion = base64_decode($ep->descripcion);
                           $epigrafe->save();
                        }
                     }
                  }
               }
            }
            
            if( $xml->cuenta AND $import_step == 3 )
            {
               $epigrafe = new epigrafe();
               foreach($xml->cuenta as $c)
               {
                  $cuenta = new cuenta();
                  if( !$cuenta->get_by_codigo($c->codcuenta, $this->ejercicio->codejercicio) )
                  {
                     $ep = $epigrafe->get_by_codigo($c->codepigrafe, $this->ejercicio->codejercicio);
                     if($ep)
                     {
                        $cuenta->idepigrafe = $ep->idepigrafe;
                        $cuenta->codepigrafe = $ep->codepigrafe;
                        $cuenta->codcuenta = $c->codcuenta;
                        $cuenta->codejercicio = $this->ejercicio->codejercicio;
                        $cuenta->descripcion = base64_decode($c->descripcion);
                        $cuenta->idcuentaesp = $c->idcuentaesp;
                        $cuenta->save();
                     }
                  }
               }
            }
            
            if( $xml->subcuenta AND $import_step == 4 )
            {
               $cuenta = new cuenta();
               foreach($xml->subcuenta as $sc)
               {
                  $subcuenta = new subcuenta();
                  if( !$subcuenta->get_by_codigo($sc->codsubcuenta, $this->ejercicio->codejercicio) )
                  {
                     $cu = $cuenta->get_by_codigo($sc->codcuenta, $this->ejercicio->codejercicio);
                     if($cu)
                     {
                        $subcuenta->idcuenta = $cu->idcuenta;
                        $subcuenta->codcuenta = $cu->codcuenta;
                        $subcuenta->coddivisa = $sc->coddivisa;
                        $subcuenta->codejercicio = $this->ejercicio->codejercicio;
                        $subcuenta->codsubcuenta = $sc->codsubcuenta;
                        $subcuenta->descripcion = base64_decode($sc->descripcion);
                        $subcuenta->save();
                     }
                  }
               }
            }
         }
         else
            $this->new_error("Imposible leer el archivo.");
      }
   }
}

?>