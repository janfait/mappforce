var width = window.innerWidth;
var height = window.innerHeight;
var p = [];
var pLength = 50;
var spring = 0.0000001;

function setup(){
  createCanvas(window.innerWidth, window.innerHeight);
  for(var i = 0; i < pLength; i++){
	p.push(new Particle(random(width),random(height)));
    p[i].vx = random(-3, 3);
    p[i].vy = random(-3, 3);
  }
}

function draw()
{ 
  background(255);
  
  for(var i = 0; i < pLength; i++)
  {
    p[i].x += p[i].vx;
    p[i].y += p[i].vy;
    
    if(p[i].x < 0)
    {
      p[i].x = width;
    }
    else if(p[i].x > width)
    {
      p[i].x = 0;
    }
    
    if(p[i].y < 0)
    {
      p[i].y = height;
    }
    else if(p[i].y > height)
    {
      p[i].y = 0;
    }
    
    for(var j = i + 1; j < pLength; j++)
    {
       springTo(p[i], p[j]);     
    }
    p[i].display();
  }
  
  //filter(BLUR, 0.5);
  
}

function springTo(p1,p2)
{
  var dx = p2.x - p1.x;
  var dy = p2.y - p1.y;
  var dist = sqrt(dx * dx + dy * dy);
  
  if(dist < 100)
  {
    var ax = dx * spring;
    var ay = dy * spring;
    var alpha = 10 + (dist/100) * 200;   
    
    p1.vx += ax;
    p1.vy += ay;
    p2.vx -= ax;
    p2.vy -= ay;
    
    stroke(alpha);
    line(p1.x, p1.y, p2.x, p2.y);
  }
}

function Particle(_x,_y)
{
    this.x = _x;
    this.y = _y;
    var vx;
    var vy;
    
    this.display = function()
    {
        ellipse(this.x, this.y, 5, 5);
    }
    
    this.move = function()
    {
      this.x += vx;
      this.y += vy;
    }
}