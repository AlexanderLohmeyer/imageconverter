import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-converter',
  templateUrl: './converter.component.html',
  styleUrls: ['./converter.component.scss']
})
export class ConverterComponent implements OnInit {

  public ENDPOINT = 'http://localhost:8000/convert.php'

  public inputPath = './input';
  public outputPath = './output';
  public maxWidth = 3840;
  public maxHeight = 2160;
  public quality = 75;

  public success = 0;
  public skipped = 0;
  public error = 0;

  public errorMsg = '';

  constructor(private http: HttpClient) { }

  ngOnInit(): void {
  }

  convert(): void {
    this.reset();

    const httpParams = new HttpParams().appendAll({
      input_path: this.inputPath,
      output_path: this.outputPath,
      max_width: this.maxWidth,
      max_height: this.maxHeight,
      quality: this.quality
    });

    this.http.post(this.ENDPOINT, httpParams, {
      headers: new HttpHeaders().set('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8')
    }).subscribe((response: any) => {
      if (response?.success) {
        this.success = response.data.converted;
        this.error = response.data.error;
        this.skipped = response.data.skipped;
      } else {
        this.errorMsg = 'Unknown Error. Please try again later';
      }});
  }

  reset(): void {
    this.success = 0;
    this.skipped = 0;
    this.error = 0;
    this.errorMsg = '';
  }

}
