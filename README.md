# 一个简单快速的只提供API服务的PHP框架 #

基于前后端分离的开发模式，将php作为纯服务器语言，只提同api接口服务的服务器框架；重新构建一个全新的构架

#### git 工具命令 ####

1. 将所有修改的文件添加到暂存区

````
git add -A
````

2. 将暂存区的内容提交到本地仓库，并注明了修改内容注释

````
git commit -m "修改的内容注释"
````

3. 将本地仓库中的修改内容推送到github中的仓库的 “main”分支中

````
git push origin main
````

4. 查看git 状态;在那个分支上

````
git status
````
5. 删除远程库文件，但本地保留该文件    rm中多文件需要 -r

````
git rm --cached xxx
````
git commit -m "remove file from remote"
````
git push -u origin master
````
