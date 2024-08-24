<?php 

namespace App;

use Error;
use GL\Math\Vec2;
use GL\VectorGraphics\{VGAlign, VGColor, VGContext};

use VISU\Graphics\{RenderTarget, Viewport, Camera, CameraProjectionMode};
use VISU\Graphics\Rendering\RenderContext;
use VISU\Geo\Transform;
use VISU\OS\{InputActionMap, Key};

use VISU\Quickstart\QuickstartApp;

class Application extends QuickstartApp
{
    /**
     * You do not have to use a camera at all if you don't want to.
     * But for sake of this example we will use one to determine a fixed viewport.
     * This is what you would typically do in a 2D game.
     */
    private Camera $camera;

    /**
     * The positon & velocity of the ball in this example
     */
    private Vec2 $ballPosition;
    private Vec2 $ballVelocity; 
    private float $ballRadius = 5.0;
    private ?Viewport $viewport = null;

    /**
     * A function that is invoked once the app is ready to run.
     * This happens exactly just before the game loop starts.
     * 
     * Here you can prepare your game state, register services, callbacks etc.
     */
    public function ready() : void
    {
        parent::ready();

        // You can bind actions to keys in VISU 
        // this way you can decouple your game logic from the actual key bindings
        // and provides a comfortable way to access input state
        $actions = new InputActionMap;
        $actions->bindButton('bounce', Key::SPACE);
        $actions->bindButton('pushRight', Key::D);
        $actions->bindButton('pushLeft', Key::A);

        $this->inputContext->registerAndActivate('main', $actions);

        // again you don't have to use a camera at all
        // we use one because in this example we don't want to couple 
        // the viewport to the actual window size
        $this->camera = new Camera(CameraProjectionMode::orthographicStaticWorld, new Transform);
        // in this quickstart example we use VG which with a camera 
        // this forces us to flip the viewport in Y direction so that -y is up
        $this->camera->flipViewportY = true;

        // load the inconsolata font to display the current score
        if ($this->vg->createFont('inconsolata', VISU_PATH_FRAMEWORK_RESOURCES_FONT . '/inconsolata/Inconsolata-Regular.ttf') === -1) {
            throw new Error('Inconsolata font could not be loaded.');
        }

        // example init 
        $this->ballPosition = new Vec2(0.0, 0.0);
        $this->ballVelocity = new Vec2(0.0, 0.0);
    }

    /**
     * Draw the scene. (You most definetly want to use this)
     * 
     * This is called from within the Quickstart render pass where the pipeline is already
     * prepared, a VG frame is also already started.
     */
    public function draw(RenderContext $context, RenderTarget $renderTarget) : void
    {
        // clear the screen
        $renderTarget->framebuffer()->clear(GL_COLOR_BUFFER_BIT | GL_STENCIL_BUFFER_BIT);

        // calculate the viewport
        $this->viewport = $this->camera->getViewport($renderTarget);
        
        // transform the VG space by the camera view
        $this->camera->transformVGSpace($this->viewport, $this->vg);

        // use the delta time to interpolate the ball position
        // you do not have to do this, but it will make the ball move buttery smooth 
        // especially on high fps screens
        $finalPos = $this->ballPosition->copy();
        $finalPos->x = $finalPos->x + $this->ballVelocity->x * $context->compensation;
        $finalPos->y = $finalPos->y + $this->ballVelocity->y * $context->compensation;

        // draw a ball
        $this->vg->beginPath();
        $this->vg->circle($this->ballPosition->x, $this->ballPosition->y, $this->ballRadius);
        $this->vg->fillColor(VGColor::red());
        $this->vg->fill();
    }

    /**
     * Update the games state
     * This method might be called multiple times per frame, or not at all if
     * the frame rate is very high.
     * 
     * The update method should step the game forward in time, this is the place
     * where you would update the position of your game objects, check for collisions
     * and so on. 
     */
    public function update() : void
    {
        parent::update();

        // handle key presses
        if ($this->inputContext->actions->didButtonPress('bounce')) {
            $this->ballVelocity->y = -3.0;
        }

        if ($this->inputContext->actions->isButtonDown('pushRight')) {
            $this->ballVelocity->x = $this->ballVelocity->x + 0.1;
        }

        if ($this->inputContext->actions->isButtonDown('pushLeft')) {
            $this->ballVelocity->x = $this->ballVelocity->x - 0.1;
        }

        // apply gravity
        $this->ballVelocity = $this->ballVelocity + new Vec2(0.0, 0.1);

        // apply friction
        $this->ballVelocity = $this->ballVelocity * 0.99;

        // apply velocity to position
        $this->ballPosition = $this->ballPosition + $this->ballVelocity;

        // we can only continue with a valid viewport
        if ($this->viewport === null) {
            return;
        }

        // check bottom boundary
        if ($this->ballPosition->y > $this->viewport->bottom - $this->ballRadius) {
            $this->ballPosition->y = $this->viewport->bottom - $this->ballRadius;
            $this->ballVelocity->y = -$this->ballVelocity->y * 0.8;
        }

        // check top boundary
        if ($this->ballPosition->y < $this->viewport->top + $this->ballRadius) {
            $this->ballPosition->y = $this->viewport->top + $this->ballRadius;
            $this->ballVelocity->y = -$this->ballVelocity->y * 0.8;
        }

        // check left boundary
        if ($this->ballPosition->x < $this->viewport->left + $this->ballRadius) {
            $this->ballPosition->x = $this->viewport->left + $this->ballRadius;
            $this->ballVelocity->x = -$this->ballVelocity->x * 0.8;
        }

        // check right boundary
        if ($this->ballPosition->x > $this->viewport->right - $this->ballRadius) {
            $this->ballPosition->x = $this->viewport->right - $this->ballRadius;
            $this->ballVelocity->x = -$this->ballVelocity->x * 0.8;
        }
    }
}