
{{ template "layout/header.tpl" }}
    <link href="/static/css/github-markdown.css" rel="stylesheet">

    <div class="container-fluid">
      {{ template "layout/navbar.tpl" }}

      <div class="row marketing">
        <div class="col-lg-12">
            <div class="col-lg-3">
                <div class="btn-group wz-nav-control">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >
                            <span class="glyphicon glyphicon glyphicon-plus" aria-hidden="true"></span>
                            新增 
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#">文档</a></li>
                            <li><a href="#">目录</a></li>
                        </ul>
                    </div>
                </div>
                <ul class="nav nav-pills nav-stacked">
                    {{ range .navbars }}
                    <li {{ if eq .ID $.current_navbar }}class="active"{{ end }}>
                        <a href="{{ .URL }}">{{ .Title }}</a>
                    </li>
                    {{ end }}
                </ul>
            </div>
            <div class="col-lg-9">
                <nav class="wz-page-control clearfix">
                    <ul class="nav nav-pills pull-right">
                        <li role="presentation"><a href="/page/1">编辑</a></li>
                        <li role="presentation"><a href="#">详情</a></li>
                        <li role="presentation" class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            更多 <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="#">分享</a></li>
                                <li><a href="#">导出</a></li>
                                <li><a href="#">复制</a></li>
                                <li><a href="/page/1/setting">配置</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>
                <div class="panel panel-default">
                    <div class="panel-body markdown-body">
                        <h1>{{ .title }}</h1>
                        {{ .content }}
                    </div>
                </div>
            </div>
        </div>
      </div>

      {{ template "layout/copyright.tpl" }}

    </div>
    
{{ template "layout/footer.tpl" }}