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

require_once 'model/albaran_cliente.php';

class general_albaranes_cli extends fs_controller
{
   public $buscar_lineas;
   public $lineas;
   public $resultados;
   public $offset;

   public function __construct()
   {
      parent::__construct('general_albaranes_cli', 'Albaranes de cliente', 'general', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['buscar_lineas']) )
         $this->buscar_lineas();
      else
      {
         $albaran = new albaran_cliente();
         $this->custom_search = TRUE;
         
         $npage = $this->page->get('general_nuevo_albaran');
         if($npage)
            $this->buttons[] = new fs_button_img('b_nuevo_albaran', 'nuevo', 'add.png', $npage->url());
         
         $agpage = $this->page->get('general_agrupar_albaranes_cli');
         if($agpage)
            $this->buttons[] = new fs_button('b_agrupar_albaranes', 'agrupar', $agpage->url());
         
         $this->buttons[] = new fs_button_img('b_buscar_lineas', 'lineas', 'zoom.png');
         
         if( isset($_POST['delete']) )
         {
            $this->delete_albaran();
         }
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         if($this->query)
            $this->resultados = $albaran->search($this->query, $this->offset);
         else
            $this->resultados = $albaran->all($this->offset);
      }
   }
   
   public function version()
   {
      return parent::version().'-7';
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_lineas_albaranes_cli';
      
      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_albaran_cliente();
      $this->lineas = $linea->search($this->buscar_lineas);
   }
   
   private function delete_albaran()
   {
      $alb1 = new albaran_cliente();
      $alb1 = $alb1->get($_POST['delete']);
      if($alb1)
      {
         /// ¿Actualizamos el stock de los artículos?
         if( isset($_POST['stock']) )
         {
            $articulo = new articulo();
            
            foreach($alb1->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($alb1->codalmacen, $linea->cantidad);
                  $art0->save();
               }
            }
         }
         
         if( $alb1->delete() )
            $this->new_message("Albarán ".$alb1->codigo." borrado correctamente.");
         else
            $this->new_error_msg("¡Imposible borrar el albarán!");
      }
      else
         $this->new_error_msg("¡Albarán no encontrado!");
   }
}

?>