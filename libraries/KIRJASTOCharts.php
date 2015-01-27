<?php
/* CATCH ERRORS:
    you should catch things like the constructor: integer must be inserted
*/

class Chart
{
    private $type;
    private $title;
    private $width;
    private $height;
    private $chartID;
    private $currency;
				
    public function __construct($type, $title, $width, $height, $chartID="", $currency = "EUR")
    {
	echo "<script type='text/javascript' src='https://www.google.com/jsapi'></script>";
	$this->type = $type;
	$this->title = $title;
	$this->width = $width;
	$this->height = $height;
        $this->currency = $currency;
        if(empty($chartID)) {
            $this->chartID = sha1(microtime()+rand(5,50));
        } else {
            $this->chartID = $chartID;
        }
    }
    public function showChart()
    {
        return "<div id='".$this->chartID."'></div>";
    }
    public function showBarChart ($columns, $rows)
    {
        echo $this->getGoogleChartStart("bar");
        foreach($columns as $value)
            echo "data.addColumn(".$value.");";

        echo "data.addRows([";
        $printThis = "";
                foreach($rows as $value)
                        $printThis .= "[".$value."],";
                echo substr($printThis,0,-1);
                echo "]);var options = {
                                width: ".$this->width.", height: ".$this->height.",
                title: '".$this->title."'
                };
                                var chart = new google.visualization.BarChart(document.getElementById('".$this->chartID."'));
                                chart.draw(data, options);
                        }
                        </script>".$this->showChart();
    }
    public function showLineChart ($columns, $rows)
    {
        echo $this->getGoogleChartStart("line");
            foreach($columns as $value)
                    echo "data.addColumn(".$value.");";
        echo "data.addRows([";
        $printThis = "";
        foreach($rows as $value)
                $printThis .= "[".$value."],";
        echo substr($printThis,0,-1);
        echo "]);var options = {
                        width: ".$this->width.", height: ".$this->height.",
        title: '".$this->title."'
        };
                        var chart = new google.visualization.LineChart(document.getElementById('".$this->chartID."'));
                        chart.draw(data, options);
                }
                </script>".$this->showChart();
    }
    public function showPieChart ($columns, $rows)
    {
        echo $this->getGoogleChartStart("pie");
            
        foreach($columns as $value)
            echo "data.addColumn(".$value.");";
        echo "data.addRows([";
        $printThis = "";
        foreach($rows as $value)
            $printThis .= "[".$value."],";
        echo substr($printThis,0,-1);
	echo "]);var options = {
                    width: ".$this->width.", height: ".$this->height.",
                    title: '".$this->title."'
                };
                var chart = new google.visualization.PieChart(document.getElementById('".$this->chartID."'));
                chart.draw(data, options);
            }
            </script>".$this->showChart();
    }
    public function showComboChart ($columns, $rows, $vAxisTitle = "", $hAxisTitle = "")
    {
        echo $this->getGoogleChartStart("combo");

        foreach($columns as $value) {
            echo "data.addColumn(".$value.");";
        }
        echo "data.addRows([";
        $printThis = "";
        foreach($rows as $value) {
            $printThis .= "[".$value."],";
        }
        echo substr($printThis,0,-1);
        echo "]);var options = {
                    'title' : '".$this->title."',
                    vAxis: {title: '".$vAxisTitle."'},
                    hAxis: {title: '".$hAxisTitle."'},
                    seriesType: 'bar',
                    series: {5: {type: 'line'}}
                };
                var chart = new google.visualization.ComboChart(document.getElementById('".$this->chartID."'));
                chart.draw(data, options);
            }
            google.setOnLoadCallback(drawVisualization);
            </script>".$this->showChart();
    }
    public function showNumbers (array $variable)
    {
        foreach($variable as $var)
            echo str_replace ("'", "", (substr($var, 0, -1)))." ".$this->currency."<br>";
    }
    private function getGoogleChartStart($chartType)
    {
        // -- this is the basic part for ALL the google charts and we add the chart-specific differencies in a switch clause below.
        $returned = "
                    <script type='text/javascript'>
                    google.load('visualization', '1', {packages:['corechart']});
                    ";
        // -- Chart-specific differencies added to the string to be returned:
        switch ($chartType):
            case "line":
            case "bar":
            case "pie":
                $returned .= "
                    google.setOnLoadCallback(drawChart);
                ";
            case "combo":
                $returned .= "
                    function drawChart() {
                    var data = new google.visualization.DataTable();
                ";
                break;
            default:
                die("Error in submitting chartType");
        endswitch;
        
        return $returned;
    }
}