<?
/** Este arquivo contém a estrutura padrão para apresentação ao usuário
 *  de algum conteúdo para web (estação de trabalho)
 *
 * @author	giuliano
 * @version	1.0 07-04-2009 14:08
 */


 class gOutput extends g_Output
{	
	function __construct($renderTo=gRENDER_REMOTE)
	{		
		parent::__construct($renderTo);
		
	}
	
	/** Concui o objeto
	 * @author	giuliano
	 * @version	1.0 07-04-2009 10:50
	 */
	function __destruct()
	{
		// checa qual o tipo de página a ser gerada (Web, PDF, XLS, etc.)
		// monta código de saída
		$this->gEnd();
		parent::__destruct();
	}
	
}

 ?>