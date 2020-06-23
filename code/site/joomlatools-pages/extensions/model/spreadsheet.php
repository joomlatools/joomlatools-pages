<?php
class ExtensionModelSpreadsheet extends ComPagesModelFilesystem
{
	public function fetchData($count = false)
	{
		if(!isset($this->_data))
		{
			$this->_data = array();
			$path        = $this->getPath($this->getState()->getValues());
			//Only fetch data if the file exists
			if(file_exists($path))
			{
				$data = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)
					->getActiveSheet()
					->toArray();
				//Get the header
				$header = array_shift($data);
				//Combine row and header
				array_walk($data, function(&$row) use ($header) {
					$row = array_combine($header, array_map('trim', $row));
				});
				$this->_data = $data;
			}
		}
		return $this->_data;
	}
}